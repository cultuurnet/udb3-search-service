<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value): void
    {
        BookingAvailability::fromString($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            'Available' => ['Available'],
            'Unavailable' => ['Unavailable'],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Booking availability does not support the value "' . $invalidValue . '"');
        BookingAvailability::fromString($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'unknown' => ['unknown'],
            'available' => ['available'],
            'AVAILABLE' => ['AVAILABLE'],
        ];
    }
}
