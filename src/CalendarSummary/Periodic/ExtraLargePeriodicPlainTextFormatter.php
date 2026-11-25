<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextDeviatingDaysFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;

final readonly class ExtraLargePeriodicPlainTextFormatter implements PeriodicFormatterInterface
{
    private DateFormatter $formatter;

    private Translator $translator;

    private PlainTextDeviatingDaysFormatter $deviatingDaysFormatter;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
        $this->deviatingDaysFormatter = new PlainTextDeviatingDaysFormatter($translator);
    }

    public function format(Offer $offer): string
    {
        $startDate = $offer->getStartDate();
        $endDate = $offer->getEndDate();

        $summary = PlainTextSummaryBuilder::start($this->translator)
            ->from(
                $this->formatter->formatAsDayOfWeek($startDate),
                $this->formatter->formatAsFullDate($startDate)
            )
            ->to(
                $this->formatter->formatAsDayOfWeek($endDate),
                $this->formatter->formatAsFullDate($endDate)
            )
            // The availability closes the period, so it stays on that line instead of
            // trailing the week scheme, which spans several lines in this format.
            ->appendAvailability($offer->getStatus(), $offer->getBookingAvailability());

        if (!$offer->getOpeningHours()->isEmpty()) {
            $summary = $summary
                ->startNewLine()
                ->append(
                    PlainTextWeekSchemeFormatter::forOpeningHours($offer->getOpeningHours(), $this->translator)
                        ->withChildcare()
                        ->withoutClosedDays()
                        ->toString()
                );
        }

        // The days without opening hours are listed among the closed days instead of in
        // the week scheme.
        $deviatingDays = $this->deviatingDaysFormatter->format(
            $offer,
            $offer->getOpeningHours()->closedDaysOfWeek()
        );

        return $summary->toString() . $deviatingDays;
    }
}
