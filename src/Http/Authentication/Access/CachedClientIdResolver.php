<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use Symfony\Contracts\Cache\CacheInterface;

final class CachedClientIdResolver implements ClientIdResolver
{
    public const SAPI_ACCESS = 'sapi_access';
    public const BOA_ACCESS = 'boa_access';

    private CacheInterface $cache;
    private ClientIdResolver $clientIdAccess;

    public function __construct(
        CacheInterface $cache,
        ClientIdResolver $clientIdAccess
    ) {
        $this->cache = $cache;
        $this->clientIdAccess = $clientIdAccess;
    }

    public function hasSapiAccess(string $clientId): bool
    {
        return $this->cache->get(
            $this->createCacheKey($clientId, self::SAPI_ACCESS),
            function () use ($clientId) {
                return $this->clientIdAccess->hasSapiAccess($clientId);
            }
        );
    }

    public function hasBoaAccess(string $clientId): bool
    {
        return $this->cache->get(
            $this->createCacheKey($clientId, self::BOA_ACCESS),
            function () use ($clientId) {
                return $this->clientIdAccess->hasBoaAccess($clientId);
            }
        );
    }

    private function createCacheKey(string $clientId, string $scope): string
    {
        return 'client_id_' . $clientId . '_' . $scope;
    }
}
