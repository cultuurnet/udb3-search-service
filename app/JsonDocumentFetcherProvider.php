<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\JsonDocument\GuzzleJsonDocumentFetcher;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcherInterface;

final class JsonDocumentFetcherProvider extends BaseServiceProvider
{
    protected $provides = [
        JsonDocumentFetcherInterface::class,
    ];

    public function register(): void
    {
        $this->add(
            JsonDocumentFetcherInterface::class,
            function () {
                return new GuzzleJsonDocumentFetcher(
                    $this->get('http_client'),
                    $this->get('logger.amqp.udb3_consumer')
                );
            }
        );
    }
}
