<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class Status
{
    private const AVAILABLE = 'Available';
    private const UNAVAILABLE = 'Unavailable';
    private const TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';

    private const ALLOWED_VALUES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
        self::TEMPORARILY_UNAVAILABLE,
    ];

    /**
     * @var string
     */
    private $status;

    public function __construct(string $status)
    {
        if (!in_array($status, self::ALLOWED_VALUES)) {
            throw new UnsupportedParameterValue(
                'Invalid Status: ' . $status . '. Should be one of ' . implode(', ', self::ALLOWED_VALUES)
            );
        }

        $this->status = $status;
    }

    public static function available(): self
    {
        return new self(self::AVAILABLE);
    }

    public static function unavailable(): self
    {
        return new self(self::UNAVAILABLE);
    }

    public static function temporarilyUnavailable(): self
    {
        return new self(self::TEMPORARILY_UNAVAILABLE);
    }

    public function toString(): string
    {
        return $this->status;
    }
}
