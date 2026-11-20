<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final class Status
{
    private const string AVAILABLE = 'Available';
    private const string UNAVAILABLE = 'Unavailable';
    private const string TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';

    private const array ALLOWED_TYPES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
        self::TEMPORARILY_UNAVAILABLE,
    ];

    private readonly string $type;

    /**
     * @param array<string, string> $reason
     */
    public function __construct(string $type, private array $reason)
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

    public function getReasonForLanguage(string $languageCode): string
    {
        if (!isset($this->reason[$languageCode])) {
            return '';
        }

        return $this->reason[$languageCode];
    }

    /**
     * @param array{type: string, reason?: array<string, string>} $data
     */
    public static function fromArray(array $data): self
    {
        $reason = [];
        if (isset($data['reason'])) {
            $reason = $data['reason'];
        }

        return new self($data['type'], $reason);
    }
}
