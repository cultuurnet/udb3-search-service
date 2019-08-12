<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Validation;

use PHPUnit\Framework\TestCase;

class PagedResultSetResponseValidatorTest extends TestCase
{
    /**
     * @var PagedResultSetResponseValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new PagedResultSetResponseValidator();
    }

    /**
     * @test
     */
    public function it_does_not_throw_an_exception_when_the_response_is_valid()
    {
        $response = [
            'hits' => [
                'total' => 20,
                'hits' => [
                    [
                        '_id' => 'acd62249-3879-469f-8f85-8df34fea109a',
                        '_source' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ],
        ];

        $this->validator->validate($response);
        $this->expectNotToPerformAssertions();
    }

    /**
     * @test
     * @dataProvider invalidResponseDataProvider
     *
     * @param string $expectedExceptionMessage
     * @param array $responseData
     */
    public function it_throws_an_exception_when_a_required_property_is_missing(
        $expectedExceptionMessage,
        array $responseData
    ) {
        $this->expectException(InvalidElasticSearchResponseException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->validator->validate($responseData);
    }

    /**
     * @return array
     */
    public function invalidResponseDataProvider()
    {
        return [
            'missing_hits' => [
                "ElasticSearch response does not contain a 'hits' property!",
                [
                    'error' => 'Something went wrong!',
                ],
            ],
            'missing_total_count' => [
                "ElasticSearch response does not contain a 'hits.total' property!",
                [
                    'hits' => [],
                ],
            ],
            'missing_results' => [
                "ElasticSearch response does not contain a 'hits.hits' property!",
                [
                    'hits' => [
                        'total' => 20,
                    ],
                ],
            ],
            'missing_result_id' => [
                "ElasticSearch response does not contain a 'hits.hits[0]._id' property!",
                [
                    'hits' => [
                        'total' => 20,
                        'hits' => [
                            [
                                '_source' => [
                                    'foo' => 'bar',
                                ],
                            ]
                        ],
                    ],
                ],
            ],
            'missing_result_source' => [
                "ElasticSearch response does not contain a 'hits.hits[0]._source' property!",
                [
                    'hits' => [
                        'total' => 20,
                        'hits' => [
                            [
                                '_id' => '36fb2f03-b0b6-4805-9ef0-17c94dee2457',
                            ]
                        ],
                    ],
                ],
            ],
        ];
    }
}
