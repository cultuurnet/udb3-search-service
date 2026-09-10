<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\DateTimeFactory;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_throw_an_exception_when_from_is_after_to(): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage(
            'Start birthdate date should be equal to or smaller than end birthdate date.'
        );

        new BirthdateRange(
            DateTimeFactory::fromAtom('2020-12-31T00:00:00+00:00'),
            DateTimeFactory::fromAtom('2020-01-01T00:00:00+00:00'),
            DateTimeFactory::fromAtom('2026-06-01T00:00:00+00:00')
        );
    }

    /**
     * @test
     */
    public function it_accepts_a_range_where_from_equals_to(): void
    {
        $date = DateTimeFactory::fromAtom('2020-01-01T00:00:00+00:00');

        $range = new BirthdateRange($date, $date, DateTimeFactory::fromAtom('2026-06-01T00:00:00+00:00'));

        $this->assertEquals($date, $range->getFrom());
        $this->assertEquals($date, $range->getTo());
    }

    /**
     * @test
     */
    public function it_accepts_a_range_where_from_is_before_to(): void
    {
        $from = DateTimeFactory::fromAtom('2020-01-01T00:00:00+00:00');
        $to = DateTimeFactory::fromAtom('2020-12-31T00:00:00+00:00');

        $range = new BirthdateRange($from, $to, DateTimeFactory::fromAtom('2026-06-01T00:00:00+00:00'));

        $this->assertEquals($from, $range->getFrom());
        $this->assertEquals($to, $range->getTo());
    }

    /**
     * @test
     */
    public function it_derives_the_age_range_relative_to_now(): void
    {
        // Relative to 2026-06-01, someone born on 2020-01-01 is 6 and someone born on 2020-12-31 is 5.
        $range = new BirthdateRange(
            DateTimeFactory::fromAtom('2020-01-01T00:00:00+00:00'),
            DateTimeFactory::fromAtom('2020-12-31T00:00:00+00:00'),
            DateTimeFactory::fromAtom('2026-06-01T00:00:00+00:00')
        );

        $this->assertSame(5, $range->getMinAge());
        $this->assertSame(6, $range->getMaxAge());
    }

    /**
     * @test
     */
    public function it_clamps_ages_for_birthdates_in_the_future_to_zero(): void
    {
        $future = DateTimeFactory::fromAtom('2030-01-01T00:00:00+00:00');

        $range = new BirthdateRange($future, $future, DateTimeFactory::fromAtom('2026-06-01T00:00:00+00:00'));

        $this->assertSame(0, $range->getMinAge());
        $this->assertSame(0, $range->getMaxAge());
    }
}
