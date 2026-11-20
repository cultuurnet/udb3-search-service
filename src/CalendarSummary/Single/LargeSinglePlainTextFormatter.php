<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\ChildcareFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final readonly class LargeSinglePlainTextFormatter implements SingleFormatterInterface
{
    private DateFormatter $formatter;

    private Translator $translator;

    /**
     * @param bool $childcareOnItsOwnLine gives the childcare a line of its own instead of
     *                                    letting it follow the date, for the sub-events of
     *                                    a multiple calendar that already are a list
     */
    public function __construct(Translator $translator, private bool $childcareOnItsOwnLine = false)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    public function format(Offer $offer): string
    {
        $startDate = $offer->getStartDate();
        $endDate = $offer->getEndDate();

        if (DateComparison::onSameDay($startDate, $endDate)) {
            $output = $this->formatSameDay($startDate, $endDate);
        } else {
            $output = $this->formatMoreDays($startDate, $endDate);
        }

        $childcare = ChildcareFormatter::forOffer($offer, $this->translator)->withBraces()->toString();

        $summary = PlainTextSummaryBuilder::start($this->translator)->append($output);

        // The availability keeps closing the date, so a childcare that gets a line of its
        // own follows it instead of splitting the two apart.
        if ($childcare !== '' && !$this->childcareOnItsOwnLine) {
            $summary = $summary->append($childcare);
        }

        $summary = $summary->appendAvailability($offer->getStatus(), $offer->getBookingAvailability());

        if ($childcare !== '' && $this->childcareOnItsOwnLine) {
            // Childcare on a line of its own is indented with a single space, like the
            // childcare of a week scheme.
            $summary = $summary->startNewLine()->append(' ' . $childcare);
        }

        return $summary->toString();
    }

    private function formatSameDay(DateTimeInterface $startDate, DateTimeInterface $endDate): string
    {
        $formattedStartDate = $this->formatter->formatAsFullDate($startDate);
        $formattedStartDayOfWeek = $this->formatter->formatAsDayOfWeek($startDate);
        $formattedStartTime = $this->formatter->formatAsTime($startDate);
        $formattedEndTime = $this->formatter->formatAsTime($endDate);

        $summaryBuilder = PlainTextSummaryBuilder::start($this->translator)
            ->append($formattedStartDayOfWeek)
            ->append($formattedStartDate);

        if ($formattedStartTime === '00:00' && $formattedEndTime === '23:59') {
            return $summaryBuilder->toString();
        }

        if ($formattedStartTime === $formattedEndTime) {
            return $summaryBuilder
                ->at($formattedStartTime)
                ->toString();
        }

        return $summaryBuilder
            ->fromHour($formattedStartTime)
            ->tillHour($formattedEndTime)
            ->toString();
    }

    private function formatMoreDays(DateTimeInterface $startDate, DateTimeInterface $endDate): string
    {
        $formattedStartDate = $this->formatter->formatAsFullDate($startDate);
        $formattedStartDayOfWeek = $this->formatter->formatAsDayOfWeek($startDate);
        $formattedStartTime = $this->formatter->formatAsTime($startDate);

        $formattedEndDate = $this->formatter->formatAsFullDate($endDate);
        $formattedEndDayOfWeek = $this->formatter->formatAsDayOfWeek($endDate);
        $formattedEndTime = $this->formatter->formatAsTime($endDate);

        return PlainTextSummaryBuilder::start($this->translator)
            ->from($formattedStartDayOfWeek, $formattedStartDate)
            ->at($formattedStartTime)
            ->tillIncluded($formattedEndDayOfWeek, $formattedEndDate)
            ->at($formattedEndTime)
            ->toString();
    }
}
