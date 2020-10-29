<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class TransformingJsonDocumentIndexService implements
    JsonDocumentIndexServiceInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var DocumentRepository
     */
    private $searchRepository;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var JsonDocumentTransformer
     */
    private $jsonDocumentTransformer;

    /**
     * @param ClientInterface $httpClient
     * @param JsonDocumentTransformer $jsonDocumentTransformer
     * @param DocumentRepository $searchRepository
     */
    public function __construct(
        ClientInterface $httpClient,
        JsonDocumentTransformer $jsonDocumentTransformer,
        DocumentRepository $searchRepository
    ) {
        $this->httpClient = $httpClient;
        $this->jsonDocumentTransformer = $jsonDocumentTransformer;
        $this->searchRepository = $searchRepository;
        $this->logger = new NullLogger();
    }

    public function index(string $documentId, string $documentIri): void
    {
        // TODO: Replace hardcoded query parameters with parameters injected inside the constructor.
        $response = $this->httpClient->request(
            'GET',
            $documentIri,
            [
                'query' => [
                    'includeMetadata' => true,
                ],
            ]
        );

        if ($response->getStatusCode() == 200) {
            $jsonLd = $response->getBody();

            $jsonDocument = new JsonDocument(
                $documentId,
                $jsonLd
            );

            $documentType = $this->searchRepository->getDocumentType();

            $this->logger->debug("Transforming {$documentType} {$documentId} for indexation.");

            $jsonDocument = $this->jsonDocumentTransformer
                ->transform($jsonDocument);

            $this->logger->debug("Transformation of {$documentType} {$documentId} finished.");

            $this->searchRepository->save($jsonDocument);
        } else {
            $this->logger->error(
                'Could not retrieve JSON-LD from url for indexation.',
                [
                    'id' => $documentId,
                    'url' => $documentIri,
                    'response' => $response,
                ]
            );
        }
    }

    public function remove(string $documentId): void
    {
        try {
            $this->searchRepository->remove($documentId);
        } catch (\Exception $exception) {
            $this->logger->error(
                'Could not remove document from repository.',
                [
                    'id' => $documentId,
                    'exception_code' => $exception->getCode(),
                    'exception_message' => $exception->getMessage(),
                ]
            );
        }
    }
}
