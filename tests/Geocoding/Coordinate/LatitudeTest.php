<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Geocoding\Coordinate;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LatitudeTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_not_accept_a_double_under_negative_90(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Latitude(-90.1);
    }

    /**
     * @test
     */
    public function it_does_not_accept_a_double_over_90(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Latitude(90.1);
    }

    /**
     * @test
     */
    public function it_accepts_any_doubles_between_negative_90_and_90(): void
    {
        new Latitude(-90.0);
        new Latitude(-5.123456789);
        new Latitude(-0.25);
        new Latitude(0.0);
        new Latitude(0.25);
        new Latitude(5.123456789);
        new Latitude(90.0);
        $this->addToAssertionCount(7);
    }
}
