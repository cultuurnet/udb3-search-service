<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use InvalidArgumentException;

final readonly class OpeningHour
{
    /**
     * @var string[]
     */
    private array $daysOfWeek;

    /**
     * @param string[] $daysOfWeek
     */
    public function __construct(
        array $daysOfWeek,
        private string $opens,
        private string $closes,
        private ?Childcare $childcare = null
    ) {
        foreach ($daysOfWeek as $day) {
            if (DayOfWeek::tryFrom($day) === null) {
                throw new InvalidArgumentException('Invalid day: ' . $day);
            }
        }
        $this->daysOfWeek = $daysOfWeek;
    }

    public static function fromArray(array $data): self
    {
        $daysOfWeek = $data['dayOfWeek'] ?? [];
        foreach ($daysOfWeek as $day) {
            if (!is_string($day)) {
                throw new InvalidArgumentException('Invalid day: ' . get_debug_type($day));
            }
        }

        return new self(
            $data['dayOfWeek'],
            $data['opens'],
            $data['closes'],
            Childcare::fromArrayOrNull($data['childcare'] ?? null)
        );
    }

    /**
     * @return string[]
     */
    public function getDaysOfWeek(): array
    {
        return $this->daysOfWeek;
    }

    public function getOpens(): string
    {
        return $this->opens;
    }

    public function getCloses(): string
    {
        return $this->closes;
    }

    public function getChildcare(): ?Childcare
    {
        return $this->childcare;
    }
}
