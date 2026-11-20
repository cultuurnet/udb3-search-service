<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final class Childcare
{
    private ?string $start;

    private ?string $end;

    /**
     * Either the start or the end can be missing, but not both: childcare only
     * before the opening hours has no end, childcare only after them has no start.
     */
    public function __construct(?string $start, ?string $end)
    {
        if ($start === null && $end === null) {
            throw new InvalidArgumentException('Childcare needs at least a start or an end');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public static function fromArray(array $data): self
    {
        return new self(self::hourOrNull($data, 'start'), self::hourOrNull($data, 'end'));
    }

    /**
     * Childcare needs at least a start or an end, anything else counts as no childcare.
     */
    public static function fromArrayOrNull(?array $data): ?self
    {
        if (self::hourOrNull($data ?? [], 'start') === null && self::hourOrNull($data ?? [], 'end') === null) {
            return null;
        }

        return self::fromArray($data ?? []);
    }

    /**
     * An hour that is there but empty says as little as one that is missing, so both count
     * as no hour at all instead of ending up in the summary as 'opvang van  tot '.
     */
    private static function hourOrNull(array $data, string $key): ?string
    {
        $hour = $data[$key] ?? null;

        return is_string($hour) && $hour !== '' ? $hour : null;
    }

    public function getStart(): ?string
    {
        return $this->start;
    }

    public function getEnd(): ?string
    {
        return $this->end;
    }

    public function equals(self $other): bool
    {
        return $this->start === $other->start && $this->end === $other->end;
    }
}
