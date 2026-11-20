<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final readonly class CalendarType
{
    private const string SINGLE = 'single';
    private const string MULTIPLE = 'multiple';
    private const string PERIODIC = 'periodic';
    private const string PERMANENT = 'permanent';

    private const array ALLOWED_VALUES = [
        self::SINGLE,
        self::MULTIPLE,
        self::PERIODIC,
        self::PERMANENT,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED_VALUES)) {
            throw new InvalidArgumentException('Invalid CalendarType: ' . $value);
        }
        $this->value = $value;
    }

    public static function single(): self
    {
        return new self(self::SINGLE);
    }

    public static function multiple(): self
    {
        return new self(self::MULTIPLE);
    }

    public static function periodic(): self
    {
        return new self(self::PERIODIC);
    }

    public static function permanent(): self
    {
        return new self(self::PERMANENT);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
