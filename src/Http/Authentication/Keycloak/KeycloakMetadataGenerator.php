<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Keycloak;

use CultuurNet\UDB3\Search\Http\Authentication\MetadataGenerator;
use CultuurNet\UDB3\Search\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class KeycloakMetadataGenerator implements MetadataGenerator
{
    private Client $client;
    private string $domain;
    private string $realm;
    private LoggerInterface $logger;

    public function __construct(
        Client $client,
        string $domain,
        string $realm
    ) {
        $this->domain = $domain;
        $this->client = $client;
        $this->realm = $realm;
        $this->logger = new NullLogger();
    }

    public function get(string $clientId, string $token): ?array
    {
        $path = '/admin/realms/' . $this->realm . '/clients/';

        $request = new Request(
            'GET',
            (new Uri($this->domain))
                ->withPath($path)
                ->withQuery(
                    http_build_query([
                        'clientId' => $clientId,
                    ])
                ),
            [
                'Authorization' => 'Bearer ' . $token,
            ]
        );

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            $message = 'Keycloak error when getting metadata: ' . $response->getStatusCode();

            if ($response->getStatusCode() >= 500) {
                $this->logger->error($message);
                throw new ConnectException(
                    $message,
                    new Request('GET', 'https://' . $this->domain . $path)
                );
            }

            $this->logger->info($message);
            return null;
        }

        $res = Json::decodeAssociatively($response->getBody()->getContents());

        if (count($res) !== 1) {
            return [];
        }

        if (!isset($res[0]['defaultClientScopes'])) {
            return [];
        }

        return $this->convertDefaultScopes($res[0]['defaultClientScopes']);
    }

    private function convertDefaultScopes(array $defaultScopes): array
    {
        $knownScopes = ['sapi', 'entry', 'ups'];

        $scopes = [];
        foreach ($knownScopes as $knownScope) {
            if (in_array('publiq-api-' . $knownScope . '-scope', $defaultScopes)) {
                $scopes[] = $knownScope;
            }
        }

        $result['publiq-apis'] = implode(' ', $scopes);
        return $result;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
