<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Http\Authentication\Auth0Client;
use CultuurNet\UDB3\Search\Http\Authentication\Auth0Token;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class GuzzleJsonDocumentFetcher implements JsonDocumentFetcher
{
    private ClientInterface $httpClient;

    private bool $includeMetadata;

    private LoggerInterface $logger;

    private Auth0Client $auth0Client;

    private ?Auth0Token $auth0Token;

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger, Auth0Client $auth0Client)
    {
        $this->httpClient = $httpClient;
        $this->includeMetadata = false;
        $this->logger = $logger;
        $this->auth0Client = $auth0Client;
        $this->auth0Token = $this->auth0Client->getToken();
    }

    public function withIncludeMetadata(): JsonDocumentFetcher
    {
        $clone = clone $this;
        $clone->includeMetadata = true;
        return $clone;
    }

    public function fetch(string $documentId, string $documentIri): ?JsonDocument
    {
        $attempt = 0;
        $maximumAttempts = 2;

        $response = $this->getResponse($documentIri);
        while ($response->getStatusCode() === 401 && $attempt < $maximumAttempts) {
            $attempt++;
            $this->refreshToken();
            $response = $this->getResponse($documentIri);
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->error(
                'Could not retrieve JSON-LD from url for indexation.',
                [
                    'id' => $documentId,
                    'url' => $documentIri,
                    'response' => $response,
                ]
            );

            return null;
        }

        return new JsonDocument($documentId, (string) $response->getBody());
    }

    private function getResponse(string $documentIri): ResponseInterface
    {
        return $this->httpClient->request(
            'GET',
            $documentIri,
            array_merge($this->getQuery($this->includeMetadata), $this->getHeader())
        );
    }

    private function getQuery(bool $includeMetadata): array
    {
        if (!$includeMetadata) {
            return [];
        }

        return [
            'query' => [
                'includeMetadata' => true,
                'embedUitpasPrices' => true,
            ],
        ];
    }

    private function refreshToken(): void
    {
        $this->auth0Token = $this->auth0Client->getToken();
    }

    private function getHeader(): array
    {
        $token = $this->auth0Token;
        if ($token === null) {
            return [];
        }

        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $token->getToken(),
            ],
        ];
    }
}
