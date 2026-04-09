<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Taxonomy;

use Symfony\Contracts\Cache\CacheInterface;

final class CachedTaxonomyApiClient implements TaxonomyApiClient
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly TaxonomyApiClient $baseTaxonomyApiClient
    ) {
    }

    public function getTypes(): array
    {
        return $this->cache->get(
            'types',
            fn () => $this->baseTaxonomyApiClient->getTypes()
        );
    }

    public function getThemes(): array
    {
        return $this->cache->get(
            'themes',
            fn () => $this->baseTaxonomyApiClient->getThemes()
        );
    }

    public function getFacilities(): array
    {
        return $this->cache->get(
            'facilities',
            fn () => $this->baseTaxonomyApiClient->getFacilities()
        );
    }
}
