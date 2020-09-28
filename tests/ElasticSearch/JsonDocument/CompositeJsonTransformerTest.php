<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CompositeJsonTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_combine_multiple_json_transformers_as_one(): void
    {
        $original = ['foo' => 'bar', 'privateProperty' => 'secretValue'];
        $inBetween = ['foo' => 'bar'];
        $final = ['foo' => 'bar', 'extraProperty' => true];

        /** @var JsonTransformer|MockObject $firstTransformer */
        $firstTransformer = $this->createMock(JsonTransformer::class);
        $firstTransformer->expects($this->once())
            ->method('transform')
            ->with($original)
            ->willReturn($inBetween);

        /** @var JsonTransformer|MockObject $secondTransformer */
        $secondTransformer = $this->createMock(JsonTransformer::class);
        $secondTransformer->expects($this->once())
            ->method('transform')
            ->with($original, $inBetween)
            ->willReturn($final);

        $compositeTransformer = new CompositeJsonTransformer($firstTransformer, $secondTransformer);

        $actual = $compositeTransformer->transform($original);

        $this->assertEquals($final, $actual);
    }
}
