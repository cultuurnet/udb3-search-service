<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\AdjustedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Period;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\MediumPermanentWeekScheme;

/**
 * Renders the periods during which the regular opening hours are replaced by
 * other opening hours as a collapsible list.
 */
final class HtmlAdjustedDaysFormatter
{
    use MediumPermanentWeekScheme;

    private DateFormatter $formatter;

    private Translator $translator;

    private HtmlPeriodListFormatter $periodListFormatter;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
        $this->periodListFormatter = new HtmlPeriodListFormatter($translator);
    }

    /**
     * @param AdjustedDay[] $adjustedDays
     */
    public function format(array $adjustedDays): string
    {
        return $this->periodListFormatter->format(
            $adjustedDays,
            'cf-adjusted-days',
            'except_during',
            fn (Period $adjustedDay): string => $adjustedDay instanceof AdjustedDay
                ? $this->generateOpeningHoursOfPeriod($adjustedDay->getOpeningHours())
                : ''
        );
    }

    private function generateOpeningHoursOfPeriod(OpeningHours $openingHours): string
    {
        // When every day of this period has the same childcare it is mentioned once
        // instead of being repeated after every timespan.
        $sharedChildcare = $openingHours->sharedChildcare();

        $output = '';
        foreach ($openingHours->groupedByIdenticalTimespans() as $group) {
            $output .= '<li>' . $this->generateOpeningHours($group, $sharedChildcare === null) . '</li>';
        }

        if ($output !== '') {
            // Every group of days gets its own line, like the week scheme of the regular opening hours.
            $output = '<ul class="list-unstyled">' . $output . '</ul>';
        }

        if ($sharedChildcare !== null) {
            $output .= '<span class="cf-childcare">'
                . ChildcareFormatter::forChildcare($sharedChildcare, $this->translator)
                    ->forEveryDay()
                    ->capitalize()
                    ->toString()
                . '</span>';
        }

        return $output;
    }

    /**
     * Renders the days of a group once, followed by every timespan they are open and the
     * childcare of those timespans.
     */
    private function generateOpeningHours(OpeningHours $group, bool $withChildcare): string
    {
        $timespans = $group->toArray();

        // The days of a group are the same on all of its timespans.
        $output = '<span class="cf-days">'
            . $this->generateDaysOfWeek($timespans[0]->getDaysOfWeek())
            . '</span>';

        $childcares = [];
        foreach ($timespans as $index => $openingHour) {
            $and = $index > 0 ? $this->translator->translate('and') . ' ' : '';
            $childcare = $openingHour->getChildcare();

            $output .= '<span class="cf-from cf-meta">' . $and
                . $this->translator->translate('from_hour') . '</span>'
                . '<span class="cf-time">' . OpeningHourFormatter::format($openingHour->getOpens()) . '</span>'
                . '<span class="cf-to cf-meta">' . $this->translator->translate('till_hour') . '</span>'
                . '<span class="cf-time">' . OpeningHourFormatter::format($openingHour->getCloses()) . '</span>';

            if ($childcare !== null) {
                $childcares[] = $childcare;
            }
        }

        // The childcare follows the last timespan, so the timespans of a day that opens more
        // than once stay together.
        if ($withChildcare && $childcares !== []) {
            $output .= '<span class="cf-childcare">'
                . ChildcareFormatter::forChildcares($this->withoutRepetition($childcares), $this->translator)
                    ->withBraces()
                    ->capitalize()
                    ->toString()
                . '</span>';
        }

        return $output;
    }

    /**
     * Keeps a childcare that every timespan shares from being mentioned once per timespan.
     *
     * @param Childcare[] $childcares
     * @return Childcare[]
     */
    private function withoutRepetition(array $childcares): array
    {
        foreach ($childcares as $childcare) {
            if (!$childcare->equals($childcares[0])) {
                return $childcares;
            }
        }

        return [$childcares[0]];
    }

    /**
     * Collapses consecutive days into a single period, e.g. 'Maandag - donderdag'.
     *
     * @param string[] $daysOfWeek
     */
    private function generateDaysOfWeek(array $daysOfWeek): string
    {
        // Index by the position in the week instead of by the locale aware day number,
        // because the first day of the week differs per locale.
        $weekDaysOpen = [];
        foreach ($daysOfWeek as $dayOfWeek) {
            $weekDaysOpen[(int) array_search($dayOfWeek, OpeningHour::ALLOWED_DAYS, true)] = $dayOfWeek;
        }

        return ucfirst(implode(', ', $this->getWeekScheme($weekDaysOpen, $this->formatter, false)));
    }
}
