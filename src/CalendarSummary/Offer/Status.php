<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final class Status
{
    private const AVAILABLE = 'Available';
    private const UNAVAILABLE = 'Unavailable';
    private const TEMPORARILY_UNAVAILABLE = 'TemporarilyUnavailable';

    private const ALLOWED_TYPES = [
        self::AVAILABLE,
        self::UNAVAILABLE,
        self::TEMPORARILY_UNAVAILABLE,
    ];

    /**
     * @var string
     */
    private $type;

    /**
     * @var string[][]
     */
    private $reason;

    public function __construct(string $type, array $reason)
    {
        if (!in_array($type, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException('Invalid status type: ' . $type);
        }
        $this->type = $type;
        $this->reason = $reason;
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
     * @param string[][] $data
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
