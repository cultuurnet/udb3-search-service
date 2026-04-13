<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Validation\ElasticSearch5;

use CultuurNet\UDB3\Search\ElasticSearch\Validation\PagedResultSetResponseValidator;
use CultuurNet\UDB3\Search\ElasticSearch5Test;
use PHPUnit\Framework\TestCase;

final class PagedResultSetResponseValidatorTest extends TestCase implements ElasticSearch5Test
{
    private PagedResultSetResponseValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PagedResultSetResponseValidator();
    }

    /**
     * @test
     */
    public function it_does_not_throw_an_exception_when_the_response_is_valid(): void
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
}
