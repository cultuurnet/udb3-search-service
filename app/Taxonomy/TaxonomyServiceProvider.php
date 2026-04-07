<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Taxonomy;

use CultuurNet\UDB3\Search\Taxonomy\JsonTaxonomyApiClient;
use CultuurNet\UDB3\Search\Taxonomy\TaxonomyApiClient;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\SearchService\Error\LoggerFactory;
use CultuurNet\UDB3\SearchService\Error\LoggerName;
use GuzzleHttp\Client;

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
    }
}
