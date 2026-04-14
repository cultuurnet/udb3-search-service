<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use DateInterval;
use DateTimeZone;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory as InMemoryKey;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;

final class JsonWebToken
{
    public const UIT_ID_V2_JWT_PROVIDER_TOKEN = 'uit_v2_jwt_provider_token';
    public const UIT_ID_V2_USER_ACCESS_TOKEN = 'uit_v2_user_access_token';
    public const UIT_ID_V2_CLIENT_ACCESS_TOKEN = 'uit_v2_client_access_token';

    private UnencryptedToken $token;

    public function __construct(string $jwt)
    {
        $token = (new Parser(new JoseEncoder()))->parse($jwt);
        // Need this assert to make PHPStan happy
        assert($token instanceof UnencryptedToken, 'Token should be an instance of UnencryptedToken');
        $this->token = $token;
    }

    public function validate(string $publicKey, ?string $keyPassphrase = null): bool
    {
        if (empty($publicKey)) {
            throw new \RuntimeException('Public key is empty');
        }

        $signer = new Sha256();
        $key = InMemoryKey::plainText($publicKey, (string)$keyPassphrase);

        $validator = new Validator();
        return $validator->validate(
            $this->token,
            new LooseValidAt(
                new SystemClock(
                    new DateTimeZone('Europe/Brussels')
                ),
                new DateInterval('PT30S')
            ),
            new SignedWith($signer, $key)
        );
    }

    public function getUserId(): string
    {
        if ($this->token->claims()->has('uid')) {
            return $this->token->claims()->get('uid');
        }

        if ($this->token->claims()->has('https://publiq.be/uitidv1id')) {
            return $this->token->claims()->get('https://publiq.be/uitidv1id');
        }

        if ($this->getType() === self::UIT_ID_V2_CLIENT_ACCESS_TOKEN && $this->token->claims()->has('azp')) {
            return $this->token->claims()->get('azp') . '@clients';
        }

        return $this->token->claims()->get('sub');
    }

    public function isAllowedOnSearchApi(?string $jwtProviderDomain): bool
    {
        $allowedApis = $this->token->claims()->get('https://publiq.be/publiq-apis', '');
        return $this->hasSapiAccess($allowedApis) && !$this->isV2JwtProviderToken($jwtProviderDomain);
    }

    private function hasSapiAccess(string $allowedApis): bool
    {
        $apis = explode(' ', $allowedApis);
        return in_array('sapi', $apis, true);
    }

    private function isV2JwtProviderToken(?string $jwtProviderDomain): bool
    {
        if ($jwtProviderDomain) {
            return $this->token->claims()->get('iss') === $jwtProviderDomain;
        }

        return $this->token->claims()->has('nickname') || $this->token->claims()->has('email');
    }

    private function getType(): string
    {
        // Because ID tokens from Keycloak always have a `azp` claim the `typ` claim can be used to verify if a Keycloak ID token is passed.
        if ($this->token->claims()->get('typ', '') === 'ID') {
            return self::UIT_ID_V2_JWT_PROVIDER_TOKEN;
        }

        // V2 client access tokens are always requested using the client-credentials grant type (gty)
        // @see https://stackoverflow.com/questions/49492471/whats-the-meaning-of-the-gty-claim-in-a-jwt-token/49492971
        if ($this->token->claims()->get('gty', '') === 'client-credentials') {
            return self::UIT_ID_V2_CLIENT_ACCESS_TOKEN;
        }

        // If all other checks fail it's a V2 user access token.
        return self::UIT_ID_V2_USER_ACCESS_TOKEN;
    }
}
