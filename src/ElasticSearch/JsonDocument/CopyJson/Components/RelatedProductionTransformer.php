<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use stdClass;

class RelatedProductionTransformer implements CopyJsonInterface
{
    public function copy(stdClass $from, stdClass $to): void
    {
        if (!isset($from->production->id)) {
            return;
        }

        $to->production = (object) [
          'id' => $from->production->id,
        ];
    }
}
