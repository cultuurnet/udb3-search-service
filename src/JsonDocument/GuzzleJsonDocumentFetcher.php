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

    private bool $includeMetadata = false;

    private bool $embedContributors = false;

    private LoggerInterface $logger;

    private Auth0Client $auth0Client;

    private ?Auth0Token $auth0Token = null;

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger, Auth0Client $auth0Client)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->auth0Client = $auth0Client;
    }

    /** @return static */
    public function withIncludeMetadata()
    {
        $clone = clone $this;
        $clone->includeMetadata = true;
        return $clone;
    }

    /** @return static */
    public function withEmbedContributors()
    {
        $clone = clone $this;
        $clone->embedContributors = true;
        return $clone;
    }

    public function fetch(string $documentId, string $documentIri): ?JsonDocument
    {
        if ($this->auth0Token === null) {
            $this->auth0Token = $this->auth0Client->getToken();
        }

        $response = $this->getResponse($documentIri);
        if ($response->getStatusCode() === 401) {
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

        return new JsonDocument($documentId, (string)$response->getBody());
    }

    private function getResponse(string $documentIri): ResponseInterface
    {
        return $this->httpClient->request(
            'GET',
            $documentIri,
            array_merge($this->getQuery(), $this->getHeader())
        );
    }

    private function getQuery(): array
    {
        if (!$this->includeMetadata && ! $this->embedContributors) {
            return [];
        }

        $params = [
            'query' => [],
        ];

        if ($this->embedContributors) {
            $params['query']['embedContributors'] = true;
        }

        if ($this->includeMetadata) {
            $params['query']['includeMetadata'] = true;
            $params['query']['embedUitpasPrices'] = true;
        }

        return $params;
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
