<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LocalTimeTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(int $value): void
    {
        new LocalTime($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            '0' => [0],
            '2359' => [2359],
            '1205' => [1205],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(int $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LocalTime($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            '-1' => [-1],
            '2360' => [2360],
        ];
    }

    /**
     * @test
     */
    public function it_reads_the_same_time_from_both_shapes(): void
    {
        $this->assertEquals(new LocalTime(830), LocalTime::tryFromString('08:30'));
    }

    /**
     * @test
     * @dataProvider validTimeProvider
     */
    public function it_puts_a_time_of_day_on_a_date(string $localTime, string $expected): void
    {
        $parsed = LocalTime::tryFromString($localTime);

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
    public function it_does_not_parse_a_string_that_is_not_a_time_of_day(string $localTime): void
    {
        $this->assertNull(LocalTime::tryFromString($localTime));
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
