<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;

final class OfferType
{
    private const EVENT = 'Event';
    private const PLACE = 'Place';

    private const ALLOWED_VALUES = [
        self::EVENT,
        self::PLACE,
    ];

    private string $offerType;

    public function __construct(string $offerType)
    {
        if (!in_array($offerType, self::ALLOWED_VALUES)) {
            throw new InvalidArgumentException(
                'Invalid OfferType: ' . $offerType . '. Should be one of ' . implode(', ', self::ALLOWED_VALUES)
            );
        }

        $this->offerType = $offerType;
    }

    public static function event(): self
    {
        return new self(self::EVENT);
    }

    public static function place(): self
    {
        return new self(self::PLACE);
    }

    public function toString(): string
    {
        return $this->offerType;
    }
}
