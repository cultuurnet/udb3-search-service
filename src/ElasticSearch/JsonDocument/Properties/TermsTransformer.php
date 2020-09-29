<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;

class TermsTransformer implements CopyJsonInterface
{
    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        $terms = $this->getTerms($from);
        if (!empty($terms)) {
            $to->terms = $terms;
        }
    }

    /**
     * @param \stdClass $object
     * @return \stdClass[]
     */
    protected function getTerms(\stdClass $object)
    {
        if (!isset($object->terms)) {
            return [];
        }

        return array_map(
            function (\stdClass $term) {
                // Don't copy all properties, just those we're interested in.
                $copy = new \stdClass();
                $copy->id = $term->id;
                $copy->label = $term->label;
                return $copy;
            },
            $object->terms
        );
    }
}
