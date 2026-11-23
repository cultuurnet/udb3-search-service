<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use DateTimeImmutable;

interface Period
{
    public function getStartDate(): DateTimeImmutable;

    public function getEndDate(): DateTimeImmutable;

    public function getDescriptionForLanguage(string $languageCode): string;
}
