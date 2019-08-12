<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Search\Offer\FacetName;

/**
 * Aggregation result, from an ElasticSearch response.
 * NOT an aggregation query.
 */
class Aggregation
{
    /**
     * @var FacetName
     */
    private $name;

    /**
     * @var Bucket[]
     */
    private $buckets;

    /**
     * @param FacetName $name
     * @param Bucket[] $buckets
     */
    public function __construct(FacetName $name, Bucket ...$buckets)
    {
        $this->name = $name;
        $this->buckets = [];

        foreach ($buckets as $bucket) {
            $this->buckets[$bucket->getKey()] = $bucket;
        }
    }

    /**
     * @return FacetName
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Bucket[]
     */
    public function getBuckets()
    {
        return $this->buckets;
    }

    /**
     * @param string $name
     * @param array $aggregationData
     * @return Aggregation
     */
    public static function fromElasticSearchResponseAggregationData($name, array $aggregationData)
    {
        if (!isset($aggregationData['buckets'])) {
            throw new \InvalidArgumentException('Aggregation data does not contain any buckets.');
        }

        $buckets = array_map(
            function (array $bucket) {
                if (!isset($bucket['key'])) {
                    throw new \InvalidArgumentException('Bucket is missing a key.');
                }

                if (!isset($bucket['doc_count'])) {
                    throw new \InvalidArgumentException('Bucket is missing a doc_count.');
                }

                return new Bucket(
                    (string) $bucket['key'],
                    (int) $bucket['doc_count']
                );
            },
            $aggregationData['buckets']
        );

        return new Aggregation(FacetName::fromNative($name), ...$buckets);
    }
}
