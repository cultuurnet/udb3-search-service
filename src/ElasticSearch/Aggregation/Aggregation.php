<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use InvalidArgumentException;
use CultuurNet\UDB3\Search\Offer\FacetName;

/**
 * Aggregation result, from an ElasticSearch response.
 * NOT an aggregation query.
 */
final class Aggregation
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
     * @param Bucket ...$buckets
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

    public static function fromElasticSearchResponseAggregationData(string $name, array $aggregationData): Aggregation
    {
        if (!isset($aggregationData['buckets'])) {
            throw new InvalidArgumentException('Aggregation data does not contain any buckets.');
        }

        $buckets = array_map(
            function (array $bucket) {
                if (!isset($bucket['key'])) {
                    throw new InvalidArgumentException('Bucket is missing a key.');
                }

                if (!isset($bucket['doc_count'])) {
                    throw new InvalidArgumentException('Bucket is missing a doc_count.');
                }

                return new Bucket(
                    (string) $bucket['key'],
                    (int) $bucket['doc_count']
                );
            },
            $aggregationData['buckets']
        );

        return new Aggregation(new FacetName($name), ...$buckets);
    }
}
