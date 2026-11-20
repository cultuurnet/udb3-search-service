<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use InvalidArgumentException;

final class OpeningHour
{
    public const ALLOWED_DAYS = [
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
    private $daysOfWeek;

    /**
     * @var string
     */
    private $opens;

    /**
     * @var string
     */
    private $closes;

    /**
     * @var Childcare|null
     */
    private $childcare;

    /**
     * @param string[] $daysOfWeek
     */
    public function __construct(
        array $daysOfWeek,
        string $opens,
        string $closes,
        ?Childcare $childcare = null
    ) {
        foreach ($daysOfWeek as $day) {
            if (!in_array($day, self::ALLOWED_DAYS)) {
                throw new InvalidArgumentException('Invalid day: ' . $day);
            }
        }
        $this->daysOfWeek = $daysOfWeek;
        $this->opens = $opens;
        $this->closes = $closes;
        $this->childcare = $childcare;
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
