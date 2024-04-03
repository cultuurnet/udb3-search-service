<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use InvalidArgumentException;
use CultuurNet\UDB3\Search\Offer\FacetName;
use PHPUnit\Framework\TestCase;

final class AggregationTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_name_and_an_associative_array_of_buckets(): void
    {
        $name = FacetName::themes();

        $buckets = [
            new Bucket('0.11.7.8.1', 10),
            new Bucket('0.11.7.8.2', 12),
        ];

        $expectedBuckets = [
            '0.11.7.8.1' => new Bucket('0.11.7.8.1', 10),
            '0.11.7.8.2' => new Bucket('0.11.7.8.2', 12),
        ];

        $aggregation = new Aggregation($name, ...$buckets);

        $this->assertEquals($name, $aggregation->getName());
        $this->assertEquals($expectedBuckets, $aggregation->getBuckets());
    }

    /**
     * @test
     */
    public function it_always_returns_an_array_of_buckets_even_if_its_empty(): void
    {
        $aggregation = new Aggregation(FacetName::regions());
        $this->assertTrue(is_array($aggregation->getBuckets()));
    }

    /**
     * @test
     */
    public function it_can_be_created_from_elasticsearch_response_aggregation_data(): void
    {
        $aggregationResponseData = [
            'doc_count_error_upper_bound' => 0,
            'sum_other_doc_count' => 0,
            'buckets' => [
                [
                    'key' => '0.11.7.8.1',
                    'doc_count' => 10,
                ],
                [
                    'key' => '0.11.7.8.2',
                    'doc_count' => 12,
                ],
            ],
        ];

        $expectedAggregation = new Aggregation(
            FacetName::themes(),
            ...[
                new Bucket('0.11.7.8.1', 10),
                new Bucket('0.11.7.8.2', 12),
            ]
        );

        $actualAggregation = Aggregation::fromElasticSearchResponseAggregationData(
            FacetName::themes()->toString(),
            $aggregationResponseData
        );

        $this->assertEquals($expectedAggregation, $actualAggregation);
    }

    /**
     * @test
     * @dataProvider invalidElasticSearchResponseAggregationDataProvider
     *
     * @param string $expectedExceptionMessage
     */
    public function it_throws_an_exception_when_the_given_elasticsearch_response_aggregation_data_is_invalid(
        array $invalidElasticSearchResponseAggregationData,
        $expectedExceptionMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        Aggregation::fromElasticSearchResponseAggregationData(
            FacetName::regions()->toString(),
            $invalidElasticSearchResponseAggregationData
        );
    }

    /**
     * @return array
     */
    public function invalidElasticSearchResponseAggregationDataProvider()
    {
        return [
            'it_checks_for_buckets_to_make_sure_the_given_data_is_of_an_aggregation' => [
                'aggregation_data' => [
                    'doc_count_error_upper_bound' => 0,
                    'sum_other_doc_count' => 0,
                ],
                'exception_message' => 'Aggregation data does not contain any buckets.',
            ],
            'it_checks_that_each_bucket_has_a_key' => [
                'aggregation_data' => [
                    'doc_count_error_upper_bound' => 0,
                    'sum_other_doc_count' => 0,
                    'buckets' => [
                        [
                            'key' => '0.11.7.8.1',
                            'doc_count' => 10,
                        ],
                        [
                            'doc_count' => 12,
                        ],
                    ],
                ],
                'exception_message' => 'Bucket is missing a key.',
            ],
            'it_checks_that_each_bucket_has_a_doc_count' => [
                'aggregation_data' => [
                    'doc_count_error_upper_bound' => 0,
                    'sum_other_doc_count' => 0,
                    'buckets' => [
                        [
                            'key' => '0.11.7.8.1',
                            'doc_count' => 10,
                        ],
                        [
                            'key' => '0.11.7.8.2',
                        ],
                    ],
                ],
                'exception_message' => 'Bucket is missing a doc_count.',
            ],
        ];
    }
}
