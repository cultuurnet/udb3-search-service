<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Http\Authentication\Token\Token;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class GuzzleJsonDocumentFetcher implements JsonDocumentFetcher
{
    private ClientInterface $httpClient;
    private LoggerInterface $logger;
    private TokenGenerator $tokenGenerator;

    private bool $includeMetadata = false;
    private bool $embedContributors = false;
    private ?Token $loginToken = null;

    public function __construct(
        ClientInterface $httpClient,
        LoggerInterface $logger,
        TokenGenerator $tokenGenerator
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
        if ($this->loginToken === null) {
            $this->loginToken = $this->tokenGenerator->loginToken();
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
                    'status_code' => $response->getStatusCode(),
                    'status_message' => $response->getReasonPhrase(),
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
        $this->loginToken = $this->tokenGenerator->loginToken();
    }

    private function getHeader(): array
    {
        if ($this->loginToken === null) {
            return [];
        }

        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->loginToken->getToken(),
            ],
        ];
    }
}
