<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class DayOfWeek
{
    private const ALLOWED_VALUES = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower($value);
        if (!in_array($normalized, self::ALLOWED_VALUES, true)) {
            throw new UnsupportedParameterValue(
                'Unknown day of week value "' . $value . '". Should be one of ' . implode(', ', self::ALLOWED_VALUES)
            );
        }

        $this->value = $normalized;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
