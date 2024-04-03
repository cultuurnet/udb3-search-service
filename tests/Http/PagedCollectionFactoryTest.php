<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Http\Hydra\PagedCollection;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\PagedResultSet;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PagedCollectionFactoryTest extends TestCase
{
    /**
     * @var JsonTransformer|MockObject
     */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = $this->createMock(JsonTransformer::class);

        $this->transformer->method('transform')
            ->willReturnCallback(
                function (array $original) {
                    $original['transformed'] = true;
                    return $original;
                }
            );
    }

    /**
     * @test
     */
    public function it_creates_a_paged_collection_from_a_paged_result_set(): void
    {
        $start = 10;
        $limit = 10;
        $total = 12;

        $pagedResultSet = new PagedResultSet(
            $total,
            $limit,
            [
                new JsonDocument(
                    '3d3ecf5c-2c21-4c6c-9faf-cd8e5fbf0464',
                    '{"@id": "events/3d3ecf5c-2c21-4c6c-9faf-cd8e5fbf0464"}'
                ),
                new JsonDocument(
                    'cd205d41-6534-4519-a38b-50937742d7ac',
                    '{"@id": "events/9f50a221-c6b3-486d-bede-603c75091dbe"}'
                ),
            ]
        );

        $expectedPageNumber = 2;

        $expectedCollection = new PagedCollection(
            $expectedPageNumber,
            $limit,
            [
                (object) [
                    '@id' => 'events/3d3ecf5c-2c21-4c6c-9faf-cd8e5fbf0464',
                    'transformed' => true,
                ],
                (object) [
                    '@id' => 'events/9f50a221-c6b3-486d-bede-603c75091dbe',
                    'transformed' => true,
                ],
            ],
            $total
        );

        $actualCollection = PagedCollectionFactory::fromPagedResultSet(
            $this->transformer,
            $pagedResultSet,
            $start,
            $limit
        );

        $this->assertEquals($expectedCollection, $actualCollection);
    }
}
