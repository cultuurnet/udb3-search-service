<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\Search\Http\Authentication\Keycloak\KeycloakTokenGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
use CultuurNet\UDB3\Search\JsonDocument\GuzzleJsonDocumentFetcher;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentFetcher;
use GuzzleHttp\Client;

final class JsonDocumentFetcherProvider extends BaseServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            JsonDocumentFetcher::class,
        ], true);
    }

    public function register(): void
    {
        $this->add(
            JsonDocumentFetcher::class,
            fn (): GuzzleJsonDocumentFetcher => new GuzzleJsonDocumentFetcher(
                new Client($this->httpClientConfig()),
                $this->get('logger.amqp.udb3'),
                $this->getTokenGenerator()
            )
        );
    }
    private function httpClientConfig(): array
    {
        $config = [
            'http_errors' => false,
        ];

        if ($this->parameter('toggles.close_http_connections') ?? true) {
            $config['headers'] = [
                'Connection' => 'close',
            ];
        }

        return $config;
    }

    private function getTokenGenerator(): TokenGenerator
    {
        return new KeycloakTokenGenerator(
            new Client(),
            $this->parameter('keycloak.domain'),
            $this->parameter('keycloak.entry_api_client_id'),
            $this->parameter('keycloak.entry_api_client_secret'),
            $this->parameter('keycloak.entry_api_audience')
        );
    }
}
