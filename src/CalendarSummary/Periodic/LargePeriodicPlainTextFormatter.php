<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

final readonly class LargePeriodicPlainTextFormatter implements PeriodicFormatterInterface
{
    private DateFormatter $formatter;

    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    public function format(Offer $offer): string
    {
        $startDate = $offer->getStartDate();
        $endDate = $offer->getEndDate();

        $formattedStartDate = $this->formatter->formatAsFullDate($startDate);
        $formattedStartDayName = $this->formatter->formatAsDayOfWeek($startDate);
        $formattedEndDate = $this->formatter->formatAsFullDate($endDate);
        $formattedEndDayName = $this->formatter->formatAsDayOfWeek($endDate);

        $summary = PlainTextSummaryBuilder::start($this->translator)
            ->from($formattedStartDayName, $formattedStartDate)
            ->to($formattedEndDayName, $formattedEndDate);

        $hasOpeningHours = !$offer->getOpeningHours()->isEmpty();

        if ($hasOpeningHours) {
            $summary = $summary
                ->startNewLine()
                ->append(
                    PlainTextWeekSchemeFormatter::forOpeningHours($offer->getOpeningHours(), $this->translator)
                        ->asSingleLine()
                        ->withChildcare()
                        ->toString()
                );
        }

        $summary = $summary->appendAvailability($offer->getStatus(), $offer->getBookingAvailability());

        // The adjusted days themselves are only listed in the extra large format, so here
        // their existence is only hinted at. Without opening hours there is nothing they
        // could differ from.
        if ($hasOpeningHours && DateComparison::withoutPastPeriods($offer->getAdjustedDays()) !== []) {
            $summary = $summary
                ->startNewLine()
                ->append($this->translator->translate('adjusted_hours_notice'));
        }

        return $summary->toString();
    }
}
