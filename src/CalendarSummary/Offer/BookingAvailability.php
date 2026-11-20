<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final readonly class BookingAvailability
{
    private const string AVAILABLE = 'Available';
    private const string UNAVAILABLE = 'Unavailable';

    private const array ALLOWED_TYPES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
    ];

    private string $type;

    public function __construct(string $type)
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException('Invalid status type: ' . $type);
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
