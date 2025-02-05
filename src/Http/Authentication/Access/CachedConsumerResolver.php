<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use Symfony\Contracts\Cache\CacheInterface;

final class CachedConsumerResolver implements ConsumerResolver
{
    private CacheInterface $cache;

    private ConsumerResolver $consumerResolver;

    public function __construct(
        CacheInterface $cache,
        ConsumerResolver $consumerResolver
    ) {
        $this->cache = $cache;
        $this->consumerResolver = $consumerResolver;
    }

    public function getStatus(string $apiKey): string
    {
        return $this->cache->get(
            $this->createCacheKey($apiKey, 'status'),
            function () use ($apiKey) {
                return $this->consumerResolver->getStatus($apiKey);
            }
        );
    }

    public function getDefaultQuery(string $apiKey): ?string
    {
        return $this->cache->get(
            $this->createCacheKey($apiKey, 'query'),
            function () use ($apiKey) {
                return $this->consumerResolver->getDefaultQuery($apiKey);
            }
        );
    }

    private function createCacheKey(string $apiKey, string $property): string
    {
        return 'consumer_id_' . $apiKey . '_' . $property;
    }
}
