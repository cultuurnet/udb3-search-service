<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\TestCase;

final class DayOfWeekTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_accepts_valid_values_and_normalises_them(string $value, string $expected): void
    {
        $this->assertSame($expected, (new DayOfWeek($value))->toString());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public function validValues(): array
    {
        return [
            'monday' => ['monday', 'monday'],
            'sunday' => ['sunday', 'sunday'],
            'mixed case' => ['Wednesday', 'wednesday'],
            'upper case' => ['FRIDAY', 'friday'],
        ];
    }

    /**
     * @test
     */
    public function it_throws_on_an_invalid_value(): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage(
            'Unknown day of week value "someday". Should be one of monday, tuesday, wednesday, thursday, friday, saturday, sunday'
        );

        new DayOfWeek('someday');
    }
}
