<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use InvalidArgumentException;

final class FallbackType
{
    private const EVENT = 'Event';
    private const PLACE = 'Place';
    private const ORGANIZER = 'Organizer';

    private static $allowedValues = [
        self::EVENT,
        self::PLACE,
        self::ORGANIZER,
    ];

    /**
     * @var string
     */
    private $fallbackType;

    public function __construct(string $fallbackType)
    {
        if (!in_array($fallbackType, self::$allowedValues)) {
            throw new InvalidArgumentException(
                'Invalid FallbackType: ' . $fallbackType . '. Should be one of ' . implode(', ', self::$allowedValues)
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
}
