<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementToken;
use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementTokenGenerator;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class GuzzleJsonDocumentFetcher implements JsonDocumentFetcher
{
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private ManagementTokenGenerator $tokenGenerator;

    private bool $includeMetadata = false;
    private bool $embedContributors = false;
    private ?ManagementToken $auth0Token = null;

    public function __construct(
        ClientInterface $httpClient,
        LoggerInterface $logger,
        ManagementTokenGenerator $tokenGenerator
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->tokenGenerator = $tokenGenerator;
    }

    public function withIncludeMetadata(): self
    {
        $clone = clone $this;
        $clone->includeMetadata = true;
        return $clone;
    }

    public function withEmbedContributors(): self
    {
        $clone = clone $this;
        $clone->embedContributors = true;
        return $clone;
    }

    public function fetch(string $documentId, string $documentIri): ?JsonDocument
    {
        if ($this->auth0Token === null) {
            $this->auth0Token = $this->tokenGenerator->newToken();
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
        if (!$this->includeMetadata && !$this->embedContributors) {
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
        $this->auth0Token = $this->tokenGenerator->newToken();
    }

    private function getHeader(): array
    {
        if ($this->auth0Token === null) {
            return [];
        }

        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->auth0Token->getToken(),
            ],
        ];
    }
}
