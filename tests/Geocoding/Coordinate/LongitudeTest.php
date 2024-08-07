<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Geocoding\Coordinate;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LongitudeTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_not_accept_a_double_under_negative_180(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Longitude(-180.1);
    }

    /**
     * @test
     */
    public function it_does_not_accept_a_double_over_180(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Longitude(180.1);
    }

    /**
     * @test
     */
    public function it_accepts_any_doubles_between_negative_180_and_180(): void
    {
        new Longitude(-180.0);
        new Longitude(-5.123456789);
        new Longitude(-0.25);
        new Longitude(0.0);
        new Longitude(0.25);
        new Longitude(5.123456789);
        new Longitude(180.0);
        $this->addToAssertionCount(7);
    }
}
