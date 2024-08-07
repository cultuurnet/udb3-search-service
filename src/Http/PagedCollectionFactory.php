<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use stdClass;
use CultuurNet\UDB3\Search\Http\Hydra\PagedCollection;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

final class PagedCollectionFactory
{
    public static function fromPagedResultSet(
        JsonTransformer $jsonTransformer,
        PagedResultSet $pagedResultSet,
        int $start,
        int $limit
    ): PagedCollection {
        $jsonDocumentTransformer = new JsonDocumentTransformer($jsonTransformer);

        $results = array_map(
            function (JsonDocument $document) use ($jsonDocumentTransformer): stdClass {
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
            $pagedResultSet->getTotal()
        );
    }
}
