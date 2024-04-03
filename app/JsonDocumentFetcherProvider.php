<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\Authentication\Auth0Client;
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
            function (): GuzzleJsonDocumentFetcher {
                return new GuzzleJsonDocumentFetcher(
                    new Client([
                        'http_errors' => false,
                    ]),
                    $this->get('logger.amqp.udb3'),
                    new Auth0Client(
                        new Client([
                            'http_errors' => false,
                        ]),
                        $this->parameter('auth0.domain'),
                        $this->parameter('auth0.entry_api_client_id'),
                        $this->parameter('auth0.entry_api_client_secret'),
                        $this->parameter('auth0.entry_api_audience')
                    )
                );
            }
        );
    }
}
