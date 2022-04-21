<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class AttendanceMode
{
    private const OFFLINE = 'offline';
    private const ONLINE = 'online';
    private const MIXED = 'mixed';

    private const ALLOWED_VALUES = [
        self::OFFLINE,
        self::ONLINE,
        self::MIXED,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED_VALUES)) {
            throw new UnsupportedParameterValue(
                'Invalid attendance mode: ' . $value . '. Should be one of ' . implode(', ', self::ALLOWED_VALUES)
            );
        }

        $this->value = $value;
    }

    public static function offline(): self
    {
        return new self(self::OFFLINE);
    }

    public static function online(): self
    {
        return new self(self::ONLINE);
    }

    public static function mixed(): self
    {
        return new self(self::MIXED);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
