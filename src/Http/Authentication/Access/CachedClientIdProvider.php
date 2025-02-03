<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\CacheItem;

final class CachedClientIdProvider implements ClientIdProvider
{
    private RedisAdapter $cache;
    private ClientIdProvider $clientIdAccess;

    public function __construct(
        RedisAdapter $cache,
        ClientIdProvider $clientIdAccess
    ) {
        $this->cache = $cache;
        $this->clientIdAccess = $clientIdAccess;
    }

    public function hasSapiAccess(string $clientId): bool
    {
        /** @var CacheItem $cachedHasSapiAccess */
        $cachedHasSapiAccess = $this->cache->getItem($clientId);
        if (!$cachedHasSapiAccess->isHit()) {
            $hasSapiAccess = $this->clientIdAccess->hasSapiAccess($clientId);
            $cachedHasSapiAccess->set($hasSapiAccess);
            $this->cache->save($cachedHasSapiAccess);
        }
        return $cachedHasSapiAccess->get();
    }
}
