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
    private UnencryptedToken $token;

    public function __construct(string $jwt)
    {
        $token = (new Parser(new JoseEncoder()))->parse($jwt);
        // Need this assert to make PHPstan happy
        assert($token instanceof UnencryptedToken, 'Token should be an instance of UnencryptedToken');
        $this->token = $token;
    }

    public function validate(string $publicKey, ?string $keyPassphrase = null): bool
    {
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

    public function isAllowedOnSearchApi(): bool
    {
        $allowedApis = $this->token->claims()->get('https://publiq.be/publiq-apis', '');
        return $this->hasSapiAccess($allowedApis) && !$this->isV2JwtProviderToken();
    }

    private function hasSapiAccess(string $allowedApis): bool
    {
        $apis = explode(' ', $allowedApis);
        return in_array('sapi', $apis, true);
    }

    private function isV2JwtProviderToken(): bool
    {
        return $this->token->claims()->has('nickname') || $this->token->claims()->has('email');
    }
}
