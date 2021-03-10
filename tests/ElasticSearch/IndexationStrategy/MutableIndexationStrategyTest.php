<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class MutableIndexationStrategyTest extends TestCase
{
    /**
     * @var IndexationStrategy|MockObject
     */
    private $mockStrategy1;

    /**
     * @var IndexationStrategy|MockObject
     */
    private $mockStrategy2;

    /**
     * @var MutableIndexationStrategy
     */
    private $mutableStrategy;

    protected function setUp()
    {
        $this->mockStrategy1 = $this->createMock(IndexationStrategy::class);
        $this->mockStrategy2 = $this->createMock(IndexationStrategy::class);

        $this->mutableStrategy = new MutableIndexationStrategy($this->mockStrategy1);
    }

    /**
     * @test
     */
    public function it_delegates_the_indexing_of_documents_to_the_currently_injected_strategy()
    {
        $index = 'udb3_core';
        $type = 'event';
        $document = new JsonDocument('ba2c3314-f50f-4f9f-b57a-1353eaaaf84c', '{"foo":"bar"}');

        $this->mockStrategy1->expects($this->once())
            ->method('indexDocument')
            ->with($index, $type, $document);

        $this->mutableStrategy->indexDocument($index, $type, $document);
    }

    /**
     * @test
     */
    public function it_can_swap_out_the_injected_strategy_for_another()
    {
        $this->mutableStrategy->setIndexationStrategy($this->mockStrategy2);

        $index = 'udb3_core';
        $type = 'event';
        $document = new JsonDocument('ba2c3314-f50f-4f9f-b57a-1353eaaaf84c', '{"foo":"bar"}');

        $this->mockStrategy1->expects($this->never())
            ->method('indexDocument');

        $this->mockStrategy2->expects($this->once())
            ->method('indexDocument')
            ->with($index, $type, $document);

        $this->mutableStrategy->indexDocument($index, $type, $document);
    }

    /**
     * @test
     */
    public function it_flushes_an_injected_bulk_strategy_before_swapping_it_out()
    {
        $this->mutableStrategy->setIndexationStrategy($this->mockStrategy1);

        $this->mockStrategy1->expects($this->once())
            ->method('finish');

        $this->mutableStrategy->setIndexationStrategy($this->mockStrategy2);
    }
}
