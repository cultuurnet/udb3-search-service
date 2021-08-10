<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;

final class BookingAvailability
{
    private const AVAILABLE = 'Available';
    private const UNAVAILABLE = 'Unavailable';

    private string $value;

    /**
     * @var string[]
     */
    private const ALLOWED_VALUES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
    ];

    private function __construct(string $value)
    {
        if (!\in_array($value, self::ALLOWED_VALUES, true)) {
            throw new InvalidArgumentException('Booking availability does not support the value "' . $value . '"');
        }
        $this->value = $value;
    }

    public static function available(): BookingAvailability
    {
        return new BookingAvailability(self::AVAILABLE);
    }

    public static function unavailable(): BookingAvailability
    {
        return new BookingAvailability(self::UNAVAILABLE);
    }

    public static function fromString(string $value): BookingAvailability
    {
        return new BookingAvailability($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
