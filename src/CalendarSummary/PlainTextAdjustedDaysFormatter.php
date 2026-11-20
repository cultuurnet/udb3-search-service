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
 * other opening hours as plain text.
 */
final class PlainTextAdjustedDaysFormatter
{
    use MediumPermanentWeekScheme;

    private DateFormatter $formatter;

    private Translator $translator;

    private PlainTextPeriodListFormatter $periodListFormatter;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
        $this->periodListFormatter = new PlainTextPeriodListFormatter($translator);
    }

    /**
     * @param AdjustedDay[] $adjustedDays
     */
    public function format(array $adjustedDays): string
    {
        return $this->periodListFormatter->format(
            $adjustedDays,
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

        $lines = [];
        foreach ($openingHours->groupedByIdenticalTimespans() as $group) {
            $lines[] = $this->generateOpeningHours($group, $sharedChildcare === null);
        }

        if ($sharedChildcare !== null) {
            $lines[] = $this->generateChildcare([$sharedChildcare], true);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Renders the days of a group once, followed by every timespan they are open and the
     * childcare of those timespans.
     */
    private function generateOpeningHours(OpeningHours $group, bool $withChildcare): string
    {
        $timespans = $group->toArray();

        // The days of a group are the same on all of its timespans.
        $day = PlainTextSummaryBuilder::start($this->translator)
            ->append($this->generateDaysOfWeek($timespans[0]->getDaysOfWeek()));

        $childcares = [];
        foreach ($timespans as $index => $openingHour) {
            // fromHour() only inserts the 'and' by itself when 'from' and 'from_hour'
            // translate to the same word, which is not the case in French.
            if ($index > 0) {
                $day = $day->and();
            }

            $day = $day
                ->fromHour(OpeningHourFormatter::format($openingHour->getOpens()))
                ->tillHour(OpeningHourFormatter::format($openingHour->getCloses()));

            $childcare = $openingHour->getChildcare();
            if ($childcare !== null) {
                $childcares[] = $childcare;
            }
        }

        $lines = [$day->toString()];

        // The childcare follows the last timespan, so the timespans of a day that opens more
        // than once stay together on one line.
        if ($withChildcare && $childcares !== []) {
            $lines[] = $this->generateChildcare($this->withoutRepetition($childcares));
        }

        return implode(PHP_EOL, $lines);
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
     * Childcare gets a line of its own, indented with a single space and between braces.
     */
    /**
     * @param Childcare[] $childcares
     */
    private function generateChildcare(array $childcares, bool $forEveryDay = false): string
    {
        $childcareFormatter = ChildcareFormatter::forChildcares($childcares, $this->translator)->withBraces();

        if ($forEveryDay) {
            $childcareFormatter = $childcareFormatter->forEveryDay();
        }

        return ' ' . $childcareFormatter->toString();
    }

    /**
     * Collapses consecutive days into a single period, e.g. 'maandag - donderdag'.
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

        return implode(', ', $this->getWeekScheme($weekDaysOpen, $this->formatter, false));
    }
}
