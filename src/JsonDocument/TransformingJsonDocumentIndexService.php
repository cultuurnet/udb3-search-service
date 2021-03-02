<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\DocumentRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class TransformingJsonDocumentIndexService implements
    JsonDocumentIndexServiceInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var JsonDocumentFetcherInterface
     */
    private $jsonDocumentFetcher;

    /**
     * @var JsonDocumentTransformer
     */
    private $jsonDocumentTransformer;

    /**
     * @var DocumentRepository
     */
    private $searchRepository;

    public function __construct(
        JsonDocumentFetcherInterface $jsonDocumentFetcher,
        JsonDocumentTransformer $jsonDocumentTransformer,
        DocumentRepository $searchRepository
    ) {
        $this->jsonDocumentFetcher = $jsonDocumentFetcher;
        $this->jsonDocumentTransformer = $jsonDocumentTransformer;
        $this->searchRepository = $searchRepository;
        $this->logger = new NullLogger();
    }

    public function index(string $documentId, string $documentIri): void
    {
        $jsonDocument = $this->jsonDocumentFetcher->fetch($documentId, $documentIri);
        if ($jsonDocument === null) {
            return;
        }

        $documentType = $this->searchRepository->getDocumentType();

        $this->logger->debug("Transforming {$documentType} {$documentId} for indexation.");

        $jsonDocument = $this->jsonDocumentTransformer
            ->transform($jsonDocument);

        $this->logger->debug("Transformation of {$documentType} {$documentId} finished.");

        $this->searchRepository->save($jsonDocument);
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
