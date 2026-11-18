<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use DateTimeImmutable;

final class ClosedDay implements Period
{
    private DateTimeImmutable $startDate;

    private DateTimeImmutable $endDate;

    /**
     * @var string[]
     */
    private array $description;

    /**
     * @param string[] $description
     */
    public function __construct(
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $description = []
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->description = $description;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            new DateTimeImmutable($data['startDate']),
            new DateTimeImmutable($data['endDate']),
            $data['description'] ?? []
        );
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getDescriptionForLanguage(string $languageCode): string
    {
        if (!isset($this->description[$languageCode])) {
            return '';
        }

        return $this->description[$languageCode];
    }
}
