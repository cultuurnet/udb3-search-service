<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Taxonomy;

use CultuurNet\UDB3\Search\Cache\CacheFactory;
use CultuurNet\UDB3\Search\Taxonomy\CachedTaxonomyApiClient;
use CultuurNet\UDB3\Search\Taxonomy\JsonTaxonomyApiClient;
use CultuurNet\UDB3\Search\Taxonomy\TaxonomyApiClient;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\SearchService\Error\LoggerFactory;
use CultuurNet\UDB3\SearchService\Error\LoggerName;
use GuzzleHttp\Client;
use Predis\Client as PredisClient;

final class TaxonomyServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        TaxonomyApiClient::class,
    ];

    public function register(): void
    {
        $this->addShared(
            TaxonomyApiClient::class,
            fn (): TaxonomyApiClient => new JsonTaxonomyApiClient(
                new Client(),
                $this->parameter('taxonomy.terms'),
                LoggerFactory::create(
                    $this->getContainer(),
                    LoggerName::forWeb()
                )
            )
        );

        $this->addShared(
            CachedTaxonomyApiClient::class,
            fn (): TaxonomyApiClient => new CachedTaxonomyApiClient(
                CacheFactory::create(
                    $this->container->get(PredisClient::class),
                    'taxonomy',
                    86400 // one day
                ),
                new JsonTaxonomyApiClient(
                    new Client(),
                    $this->parameter('taxonomy.terms'),
                    LoggerFactory::create(
                        $this->getContainer(),
                        LoggerName::forWeb()
                    )
                )
            )
        );
    }
}
