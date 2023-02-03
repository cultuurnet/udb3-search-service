<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory as InMemoryKey;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256;

final class JsonWebTokenFactory
{
    public static function createWithClaims(array $claims): string
    {
        $builder = new Builder(new JoseEncoder(), new ChainedFormatter());
        foreach ($claims as $claim => $value) {
            $builder = $builder->withClaim($claim, $value);
        }

        return $builder->getToken(
            new Sha256(),
            InMemoryKey::plainText(
                file_get_contents('file://' . __DIR__ . '/samples/private.pem'),
                'secret'
            )
        )->toString();
    }

    public static function createWithInvalidSignature(): string
    {
        return (new Builder(new JoseEncoder(), new ChainedFormatter()))->getToken(
            new Sha256(),
            InMemoryKey::plainText(
                file_get_contents('file://' . __DIR__ . '/samples/private-invalid.pem'),
                'secret'
            )
        )->toString();
    }

    public static function getPublicKey(): string
    {
        return file_get_contents('file://' . __DIR__ . '/samples/public.pem');
    }
}
