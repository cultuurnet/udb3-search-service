<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;
use Iterator;
use PHPUnit\Framework\TestCase;

final class DayOfWeekTest extends TestCase
{
    /**
     * @test
     * @dataProvider dayOfWeekValueProvider
     */
    public function it_maps_cases_to_lowercase_english_values(DayOfWeek $day, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $day->value);
    }

    /**
     * @return Iterator<string, array{0: DayOfWeek, 1: string}>
     */
    public function dayOfWeekValueProvider(): Iterator
    {
        yield 'monday' => [DayOfWeek::Monday, 'monday'];
        yield 'tuesday' => [DayOfWeek::Tuesday, 'tuesday'];
        yield 'wednesday' => [DayOfWeek::Wednesday, 'wednesday'];
        yield 'thursday' => [DayOfWeek::Thursday, 'thursday'];
        yield 'friday' => [DayOfWeek::Friday, 'friday'];
        yield 'saturday' => [DayOfWeek::Saturday, 'saturday'];
        yield 'sunday' => [DayOfWeek::Sunday, 'sunday'];
    }

    /**
     * @test
     */
    public function it_derives_the_day_of_week_from_a_date(): void
    {
        // 2024-06-05 is a Wednesday.
        $this->assertSame(DayOfWeek::Wednesday, DayOfWeek::fromDate(new DateTimeImmutable('2024-06-05')));
    }

    /**
     * @test
     */
    public function it_parses_a_day_of_week_from_a_string(): void
    {
        $this->assertSame(DayOfWeek::Wednesday, DayOfWeek::fromString('wednesday'));
    }

    /**
     * @test
     */
    public function it_parses_a_day_of_week_case_insensitively(): void
    {
        $this->assertSame(DayOfWeek::Friday, DayOfWeek::fromString('FrIdAY'));
    }

    /**
     * @test
     */
    public function it_parses_a_day_of_week_surrounded_by_whitespace(): void
    {
        $this->assertSame(DayOfWeek::Saturday, DayOfWeek::fromString(' saturday '));
    }

    /**
     * @test
     */
    public function it_reports_the_trimmed_value_for_an_unknown_day_of_week(): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('Unknown day of week value "someday"');

        DayOfWeek::fromString(' someday ');
    }

    /**
     * @test
     */
    public function it_rejects_an_unknown_day_of_week(): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('Unknown day of week value "someday"');

        DayOfWeek::fromString('someday');
    }
}
