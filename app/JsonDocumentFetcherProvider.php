<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\JsonDocument\GuzzleJsonDocumentFetcher;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use GuzzleHttp\Client;

final class JsonDocumentFetcherProvider extends BaseServiceProvider
{
    protected $provides = [
        JsonDocumentFetcher::class,
    ];

    public function register(): void
    {
        $this->add(
            JsonDocumentFetcher::class,
            function () {
                return new GuzzleJsonDocumentFetcher(
                    new Client(),
                    $this->get('logger.amqp.udb3')
                );
            }
        );
    }
}
