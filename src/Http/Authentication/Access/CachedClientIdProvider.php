<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use Symfony\Contracts\Cache\CacheInterface;

final class CachedClientIdProvider implements ClientIdProvider
{
    private CacheInterface $cache;
    private ClientIdProvider $clientIdAccess;

    public function __construct(
        CacheInterface $cache,
        ClientIdProvider $clientIdAccess
    ) {
        $this->cache = $cache;
        $this->clientIdAccess = $clientIdAccess;
    }

    public function hasSapiAccess(string $clientId): bool
    {
        return $this->cache->get(
            $clientId,
            function () use ($clientId) {
                return $this->clientIdAccess->hasSapiAccess($clientId);
            }
        );
    }
}
