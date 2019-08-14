<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;

class CopyJsonLabels implements CopyJsonInterface
{
    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        $labels = $this->getLabels($from);

        if (!empty($labels)) {
            $to->labels = $labels;
        }
    }

    /**
     * @param \stdClass $object
     * @return array
     */
    private function getLabels(\stdClass $object)
    {
        $labels = [];

        if (isset($object->labels)) {
            $labels = array_merge($labels, $object->labels);
        }

        if (isset($object->hiddenLabels)) {
            $labels = array_merge($labels, $object->hiddenLabels);
        }

        return $labels;
    }
}
