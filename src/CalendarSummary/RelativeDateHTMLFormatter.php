<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use DateTimeInterface;

trait RelativeDateHTMLFormatter
{
    public function getRelativeDate(DateTimeInterface $date, Translator $translator, DateFormatter $formatter): ?string
    {
        if (DateComparison::isThisEvening($date)) {
            return '<span class="cf-days">' . $translator->translate('tonight') . '</span>';
        }
        if (DateComparison::isToday($date)) {
            return '<span class="cf-days">' . $translator->translate('today') . '</span>';
        }
        if (DateComparison::isTomorrow($date)) {
            return '<span class="cf-days">' . $translator->translate('tomorrow') . '</span>';
        }
        if (DateComparison::isUpcomingDayInCurrentWeek($date)) {
            return '<span class="cf-meta">' . $translator->translate('this') . '</span>' .
                ' ' .
                '<span class="cf-days">' . $formatter->formatAsDayOfWeek($date) . '</span>';
        }
        return null;
    }
}
