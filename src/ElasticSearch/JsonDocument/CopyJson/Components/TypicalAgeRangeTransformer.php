<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;

class TypicalAgeRangeTransformer implements CopyJsonInterface
{
    /**
     * @inheritdoc
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        if (!isset($from->typicalAgeRange) || !is_string($from->typicalAgeRange)) {
            return;
        }

        $regexMatches = [];
        preg_match('/(\d*)-(\d*)/', $from->typicalAgeRange, $regexMatches);


        if (count($regexMatches) !== 3) {
            // The matches should always contain exactly 3 values:
            // 0: The delimiter (-)
            // 1: minAge as string (or empty string)
            // 2: maxAge as string (or empty string)
            return;
        }

        // Be sure to always do a strict comparison here!
        $minAge = ($regexMatches[1] !== '') ? (int) $regexMatches[1] : 0;
        $maxAge = ($regexMatches[2] !== '') ? (int) $regexMatches[2] : null;

        $to->typicalAgeRange = new \stdClass();
        $to->typicalAgeRange->gte = $minAge;

        if ($maxAge) {
            $to->typicalAgeRange->lte = $maxAge;
        }

        $to->allAges = ($minAge === 0 && is_null($maxAge));
    }
}
