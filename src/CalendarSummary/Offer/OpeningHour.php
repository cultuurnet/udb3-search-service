<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final readonly class OpeningHour
{
    public const array ALLOWED_DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

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
            if (!in_array($day, self::ALLOWED_DAYS)) {
                throw new InvalidArgumentException('Invalid day: ' . $day);
            }
        }
        $this->daysOfWeek = $daysOfWeek;
    }

    public static function fromArray(array $data): self
    {
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
