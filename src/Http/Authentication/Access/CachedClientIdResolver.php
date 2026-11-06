<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use Symfony\Contracts\Cache\CacheInterface;

final class CachedClientIdResolver implements ClientIdResolver
{
    public const SAPI_ACCESS = 'sapi_access';

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ClientIdResolver $clientIdAccess
    ) {
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

    private function createCacheKey(string $clientId, string $scope): string
    {
        return 'client_id_' . $clientId . '_' . $scope;
    }
}
