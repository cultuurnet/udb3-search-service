<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentTransformerInterface;

/**
 * Converts Event, Place and Organizer results to minimal documents that only
 * contain @id and @type.
 * Should be used when returning search results.
 */
class MinimalRequiredInfoJsonDocumentTransformer implements JsonDocumentTransformerInterface
{
    /**
     * @param JsonDocument $jsonDocument
     * @return JsonDocument
     */
    public function transform(JsonDocument $jsonDocument)
    {
        $body = $jsonDocument->getBody();

        $newBody = (object) [
            '@id' => $body->{'@id'},
            '@type' => $body->{'@type'},
        ];

        return $jsonDocument->withBody($newBody);
    }
}
