<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NaturalTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_a_value_bigger_then_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getMockForAbstractClass(
            Natural::class,
            [-1]
        );
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_a_string(): void
    {
        /** @var Natural $natural */
        $natural = $this->getMockForAbstractClass(
            Natural::class,
            [99]
        );

        $this->assertEquals('99', $natural->toString());
    }

    /**
     * @test
     */
    public function it_can_return_the_value(): void
    {
        /** @var Natural $natural */
        $natural = $this->getMockForAbstractClass(
            Natural::class,
            [99]
        );

        $this->assertEquals(99, $natural->toNative());
    }
}
