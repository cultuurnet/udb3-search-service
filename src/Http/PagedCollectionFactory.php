<?php

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;
use CultuurNet\UDB3\Search\PagedResultSet;

class PagedCollectionFactory
{
    public static function fromPagedResultSet(
        JsonDocumentTransformerInterface $jsonDocumentTransformer,
        PagedResultSet $pagedResultSet,
        int $start,
        int $limit
    ): PagedCollection {
        $results = array_map(
            function (JsonDocument $document) use ($jsonDocumentTransformer) {
                $document = $jsonDocumentTransformer->transform($document);
                return $document->getBody();
            },
            $pagedResultSet->getResults()
        );

        $pageNumber = (int) floor($start / $limit) + 1;

        return new PagedCollection(
            $pageNumber,
            $limit,
            $results,
            $pagedResultSet->getTotal()->toNative()
        );
    }
}
