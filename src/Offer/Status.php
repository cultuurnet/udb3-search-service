<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;

final class Status
{
    public const AVAILABLE = 'Available';
    public const UNAVAILABLE = 'Unavailable';
    public const TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';

    /**
     * @var string
     */
    private $status;

    public function __construct(string $status)
    {
        if (!in_array($status, $this->getAllowedValues())) {
            throw new InvalidArgumentException('The given status ' . $status . ' is not supported');
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

    private function getAllowedValues(): array
    {
        return [
            self::AVAILABLE,
            self::UNAVAILABLE,
            self::TEMPORARILY_UNAVAILABLE,
        ];
    }
}
