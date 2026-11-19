<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final readonly class ExtraSmallSinglePlainTextFormatter implements SingleFormatterInterface
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
        $dateParts = [];
        $dateParts[] = $this->formatter->formatAsDayNumber($date);
        $dateParts[] = $this->formatter->formatAsAbbreviatedMonthName($date);
        if (!DateComparison::isCurrentYear($date)) {
            $dateParts[] = $this->formatter->formatAsYear($date);
        }

        return PlainTextSummaryBuilder::singleLine(...$dateParts);
    }

    private function formatMoreDays(DateTimeInterface $startDate, DateTimeInterface $endDate): string
    {
        $startDay = [];
        $startDay[] = $this->formatter->formatAsDayNumber($startDate);
        $startDay[] = $this->formatter->formatAsAbbreviatedMonthName($startDate);
        if (!DateComparison::isCurrentYear($startDate)) {
            $startDay[] = $this->formatter->formatAsYear($startDate);
        }

        $endDay = [];
        $endDay[] = $this->formatter->formatAsDayNumber($endDate);
        $endDay[] = $this->formatter->formatAsAbbreviatedMonthName($endDate);
        if (!DateComparison::isCurrentYear($endDate)) {
            $endDay[] = $this->formatter->formatAsYear($endDate);
        }

        return PlainTextSummaryBuilder::start($this->translator)
            ->fromTill($startDay, $endDay)
            ->toString();
    }
}
