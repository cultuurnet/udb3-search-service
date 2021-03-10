<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use InvalidArgumentException;

final class FallbackType
{
    public const EVENT = 'Event';
    public const PLACE = 'Place';
    public const ORGANIZER = 'Organizer';

    /**
     * @var string
     */
    private $fallbackType;

    public function __construct(string $fallbackType)
    {
        if (!\in_array($fallbackType, $this->getAllowedValues())) {
            throw new InvalidArgumentException(
                'The given fallback type ' . $fallbackType . ' is not Event, Place or Organizer'
            );
        }

        $this->fallbackType = $fallbackType;
    }

    public static function event(): self
    {
        return new self(self::EVENT);
    }

    public static function place(): self
    {
        return new self(self::PLACE);
    }

    public static function organizer(): self
    {
        return new self(self::ORGANIZER);
    }

    public function toString(): string
    {
        return $this->fallbackType;
    }

    private function getAllowedValues(): array
    {
        return [
            self::EVENT,
            self::PLACE,
            self::ORGANIZER,
        ];
    }
}
