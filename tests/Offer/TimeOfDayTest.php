<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TimeOfDayTest extends TestCase
{
    /**
     * @test
     * @dataProvider validTimeProvider
     */
    public function it_puts_a_time_of_day_on_a_date(string $timeOfDay, string $expected): void
    {
        $parsed = TimeOfDay::tryFromString($timeOfDay);

        $this->assertSame(
            $expected,
            $parsed?->on(new DateTimeImmutable('2024-06-01T15:30:45+02:00'))->format('Y-m-d\TH:i:sP')
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function validTimeProvider(): array
    {
        return [
            'two digit hours' => ['08:00', '2024-06-01T08:00:00+02:00'],
            'one digit hours' => ['8:00', '2024-06-01T08:00:00+02:00'],
            'midnight' => ['00:00', '2024-06-01T00:00:00+02:00'],
            'last minute of the day' => ['23:59', '2024-06-01T23:59:00+02:00'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidTimeProvider
     */
    public function it_does_not_parse_a_string_that_is_not_a_time_of_day(string $timeOfDay): void
    {
        $this->assertNull(TimeOfDay::tryFromString($timeOfDay));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public function invalidTimeProvider(): array
    {
        return [
            'words' => ['noon'],
            'empty' => [''],
            'hours out of range' => ['24:00'],
            'minutes out of range' => ['12:60'],
            'seconds included' => ['12:00:00'],
            'without a separator' => ['1200'],
        ];
    }
}
