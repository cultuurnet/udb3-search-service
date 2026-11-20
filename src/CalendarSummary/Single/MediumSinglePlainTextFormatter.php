<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final readonly class MediumSinglePlainTextFormatter implements SingleFormatterInterface
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

        if (DateComparison::onSameDay($startDate, $endDate)) {
            $output = $this->formatSameDay($startDate);
        } else {
            $output = $this->formatMoreDays($startDate, $endDate);
        }

        return PlainTextSummaryBuilder::start($this->translator)
            ->append($output)
            ->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
            ->toString();
    }

    private function formatSameDay(DateTimeInterface $date): string
    {
        $formattedDate = $this->formatter->formatAsFullDate($date);
        $formattedWeekDay = $this->formatter->formatAsAbbreviatedDayOfWeek($date);
        return PlainTextSummaryBuilder::singleLine($formattedWeekDay, $formattedDate);
    }

    private function formatMoreDays(DateTimeInterface $startDate, DateTimeInterface $endDate): string
    {
        $formattedStartDate = $this->formatter->formatAsFullDate($startDate);
        $formattedStartDayOfWeek = $this->formatter->formatAsAbbreviatedDayOfWeek($startDate);

        $formattedEndDate = $this->formatter->formatAsFullDate($endDate);
        $formattedEndDayOfWeek = $this->formatter->formatAsAbbreviatedDayOfWeek($endDate);

        return PlainTextSummaryBuilder::start($this->translator)
            ->from($formattedStartDayOfWeek, $formattedStartDate)
            ->till($formattedEndDayOfWeek, $formattedEndDate)
            ->toString();
    }
}
