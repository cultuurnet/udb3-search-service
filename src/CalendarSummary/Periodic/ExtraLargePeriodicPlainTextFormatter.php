<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextDeviatingDaysFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;

final class ExtraLargePeriodicPlainTextFormatter implements PeriodicFormatterInterface
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
            );

        if (!$offer->getOpeningHours()->isEmpty()) {
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

        return $summary->toString() . $this->deviatingDaysFormatter->format($offer);
    }
}
