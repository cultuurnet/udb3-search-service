<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use DateTimeInterface;

trait RelativeDatePlainTextFormatter
{
    public function getRelativeDate(DateTimeInterface $date, Translator $translator, DateFormatter $formatter): ?string
    {
        if (DateComparison::isThisEvening($date)) {
            return $translator->translate('tonight');
        }
        if (DateComparison::isToday($date)) {
            return $translator->translate('today');
        }
        if (DateComparison::isTomorrow($date)) {
            return $translator->translate('tomorrow');
        }
        if (DateComparison::isUpcomingDayInCurrentWeek($date)) {
            $preposition = $translator->translate('this');
            $weekDay = $formatter->formatAsDayOfWeek($date);
            return PlainTextSummaryBuilder::singleLine($preposition, $weekDay);
        }
        return null;
    }
}
