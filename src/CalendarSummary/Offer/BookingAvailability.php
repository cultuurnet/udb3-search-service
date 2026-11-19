<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final class BookingAvailability
{
    private const AVAILABLE = 'Available';
    private const UNAVAILABLE = 'Unavailable';

    private const ALLOWED_TYPES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
    ];

    /**
     * @var string
     */
    private $type;

    public function __construct(string $type)
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException('Invalid booking availability type: ' . $type);
        }
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isAvailable(): bool
    {
        return $this->type === self::AVAILABLE;
    }

    /**
     * @param string[] $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['type']);
    }
}
