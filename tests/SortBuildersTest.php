<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use PHPUnit\Framework\TestCase;

final class SortBuildersTest extends TestCase
{
    public function testSortsAndBuildersAreMatching(): void
    {
        $sorts = ['score' => 'asc', 'popularity' => 'desc'];
        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)->getMock();
        $sortBuilders = [
            'score' => fn () => $queryBuilderMock,
            'popularity' => fn () => $queryBuilderMock,
        ];

        $resultQueryBuilder = (new SortBuilders())->build($sorts, $sortBuilders, $queryBuilderMock);

        $this->assertInstanceOf(QueryBuilder::class, $resultQueryBuilder);
    }

    public function testGetQueryBuilderUnsupportedSort(): void
    {
        $sorts = ['does_not_exist' => 'asc'];
        $sortBuilders = [
            'score' => fn () => $this->getMockBuilder(OfferQueryBuilderInterface::class)->getMock(),
        ];
        $queryBuilderMock = $this->getMockBuilder(QueryBuilder::class)
            ->getMock();

        $this->expectException(UnsupportedParameterValue::class);

        (new SortBuilders())->build($sorts, $sortBuilders, $queryBuilderMock);
    }
}
