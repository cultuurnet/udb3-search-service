<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;

class OriginalEncodedJsonLdTransformer implements CopyJsonInterface
{
    /**
     * @inheritdoc
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        $to->originalEncodedJsonLd = json_encode($from, JSON_UNESCAPED_SLASHES);
    }
}
