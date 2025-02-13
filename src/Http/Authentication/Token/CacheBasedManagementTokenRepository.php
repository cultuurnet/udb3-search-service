<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Token;

use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheBasedManagementTokenRepository implements ManagementTokenRepository
{
    private const KEY = 'keycloak_management_token';
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function get(): ?Token
    {
        $cachedToken = $this->cache->get(
            self::KEY,
            fn () => null,
        );

        if ($cachedToken === null) {
            return null;
        }

        $tokenAsArray = Json::decodeAssociatively($cachedToken);

        return new Token(
            $tokenAsArray['token'],
            new DateTimeImmutable($tokenAsArray['issuesAt']),
            $tokenAsArray['expiresIn']
        );
    }

    public function set(Token $token): void
    {
        $this->cache->delete(self::KEY);

        $this->cache->get(
            self::KEY,
            fn () => Json::encode([
                'token' => $token->getToken(),
                'issuesAt' => $token->getIssuedAt()->format(DATE_ATOM),
                'expiresIn' => $token->getExpiresIn(),
            ])
        );
    }
}
