<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use DateTimeImmutable;

final class AdjustedDay implements Period
{
    private DateTimeImmutable $startDate;

    private DateTimeImmutable $endDate;

    private OpeningHours $openingHours;

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
        ?OpeningHours $openingHours = null,
        array $description = []
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->openingHours = $openingHours ?? new OpeningHours();
        $this->description = $description;
    }

    public static function fromArray(array $data): self
    {
        $openingHours = [];
        if (isset($data['openingHours'])) {
            foreach ($data['openingHours'] as $openingHourData) {
                $openingHours[] = OpeningHour::fromArray($openingHourData);
            }
        }

        return new self(
            new DateTimeImmutable($data['startDate']),
            new DateTimeImmutable($data['endDate']),
            new OpeningHours($openingHours),
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

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }

    public function getDescriptionForLanguage(string $languageCode): string
    {
        if (!isset($this->description[$languageCode])) {
            return '';
        }

        return $this->description[$languageCode];
    }
}
