<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\Authentication\Auth0\Auth0TokenGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Keycloak\KeycloakTokenGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
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
            fn (): GuzzleJsonDocumentFetcher => new GuzzleJsonDocumentFetcher(
                new Client([
                    'http_errors' => false,
                ]),
                $this->get('logger.amqp.udb3'),
                $this->getTokenGenerator()
            )
        );
    }

    private function getTokenGenerator(): TokenGenerator
    {
        if (true) {
            return new KeycloakTokenGenerator(
                new Client(),
                $this->parameter('keycloak.domain'),
                $this->parameter('keycloak.entry_api_client_id'),
                $this->parameter('keycloak.entry_api_client_secret'),
                $this->parameter('keycloak.entry_api_audience')
            );
        }

        return new Auth0TokenGenerator(
            new Client([
                'http_errors' => false,
            ]),
            $this->parameter('auth0.domain'),
            $this->parameter('auth0.entry_api_client_id'),
            $this->parameter('auth0.entry_api_client_secret'),
            $this->parameter('auth0.entry_api_audience')
        );
    }
}
