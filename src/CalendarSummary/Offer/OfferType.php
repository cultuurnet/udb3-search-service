<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final readonly class OfferType
{
    private const string EVENT = 'event';
    private const string PLACE = 'place';

    private const array ALLOWED_VALUES = [
        self::EVENT,
        self::PLACE,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::ALLOWED_VALUES)) {
            throw new InvalidArgumentException('Invalid CalendarType: ' . $value);
        }
        $this->value = $value;
    }

    public static function fromContext(string $context): self
    {
        if ($context === '/contexts/place') {
            return self::place();
        }

        if ($context === '/contexts/event') {
            return self::event();
        }

        throw new InvalidArgumentException('Unknown context: ' . $context);
    }

    public static function event(): self
    {
        return new self(self::EVENT);
    }

    public static function place(): self
    {
        return new self(self::PLACE);
    }

    public function equals(OfferType $other): bool
    {
        return $this->value === $other->value;
    }
}
