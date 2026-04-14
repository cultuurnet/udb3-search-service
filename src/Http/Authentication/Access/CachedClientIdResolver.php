<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use Symfony\Contracts\Cache\CacheInterface;

final class CachedClientIdResolver implements ClientIdResolver
{
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
            'client_id_' . $clientId . '_sapi_access',
            function () use ($clientId) {
                return $this->clientIdAccess->hasSapiAccess($clientId);
            }
        );
    }

    public function hasBoaAccess(string $clientId): bool
    {
        return $this->cache->get(
            'client_id_' . $clientId . '_boa_access',
            function () use ($clientId) {
                return $this->clientIdAccess->hasBoaAccess($clientId);
            }
        );
    }
}
