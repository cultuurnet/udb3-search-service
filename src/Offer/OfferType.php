<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;

final class OfferType
{
    public const EVENT = 'Event';
    public const PLACE = 'Place';

    /**
     * @var string
     */
    private $offerType;

    public function __construct(string $offerType)
    {
        if (!in_array($offerType, $this->getAllowedValues())) {
            throw new InvalidArgumentException('The given offer type ' . $offerType . ' should be Event or Place');
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

    private function getAllowedValues(): array
    {
        return [
            self::EVENT,
            self::PLACE,
        ];
    }
}
