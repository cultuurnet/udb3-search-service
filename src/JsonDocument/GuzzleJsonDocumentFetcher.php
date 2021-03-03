<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

final class GuzzleJsonDocumentFetcher implements JsonDocumentFetcher
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var bool
     */
    private $includeMetadata;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->includeMetadata = false;
        $this->logger = $logger;
    }

    public function withIncludeMetadata(): JsonDocumentFetcher
    {
        $clone = clone $this;
        $clone->includeMetadata = true;
        return $clone;
    }

    public function fetch(string $documentId, string $documentIri): ?JsonDocument
    {
        $response = $this->httpClient->request(
            'GET',
            $documentIri,
            $this->getQuery($this->includeMetadata)
        );

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

    private function getQuery(bool $includeMetadata): array
    {
        if (!$includeMetadata) {
            return [];
        }

        return [
            'query' => [
                'includeMetadata' => true,
            ],
        ];
    }
}
