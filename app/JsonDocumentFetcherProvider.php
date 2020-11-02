<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;

class JsonDocumentFetcherProvider extends BaseServiceProvider
{
    protected $provides = [
        JsonDocumentFetcher::class,
    ];

    public function register(): void
    {
        $this->add(
            JsonDocumentFetcher::class,
            function () {
                return new JsonDocumentFetcher(
                    $this->get('http_client'),
                    $this->get('logger.amqp.udb3_consumer')
                );
            }
        );
    }
}
