<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

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
    private string $jwt;

    private UnencryptedToken $token;

    public function __construct(string $jwt)
    {
        $this->token = (new Parser(new JoseEncoder()))->parse($jwt);
    }

    public function validate(string $publicKey, ?string $keyPassphrase = null): bool
    {
        $signer = new Sha256();
        $key =  InMemoryKey::file($publicKey, $keyPassphrase);

        $validator = new Validator();
        $valid = $validator->validate(
            $this->token,
            new LooseValidAt(
                new SystemClock(
                    new \DateTimeZone('Europe/Brussels')
                ),
                new \DateInterval('PT30S')
            ),
            new SignedWith($signer, $key)
        );
        if (!$valid) {
            return false;
        }
        $allowedApis = $this->token->claims()->get('https://publiq.be/publiq-apis', '');

        $this->token->claims()->has('nickname') || $this->token->claims()->has('email');

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
