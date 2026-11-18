<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final class SmallPeriodicPlainTextFormatter implements PeriodicFormatterInterface
{
    /**
     * @var DateFormatter
     */
    private $formatter;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    public function format(Offer $offer): string
    {
        $startDate = $offer->getStartDate();
        $startDate = $startDate->setTime(0, 0, 1);

        if (DateComparison::isInTheFuture($startDate)) {
            return PlainTextSummaryBuilder::start($this->translator)
                ->fromPeriod($this->formatDate($startDate))
                ->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
                ->toString();
        }

        $endDate = $offer->getEndDate();
        return PlainTextSummaryBuilder::start($this->translator)
            ->till($this->formatDate($endDate))
            ->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
            ->toString();
    }

    private function formatDate(DateTimeInterface $date): string
    {
        $formattedDate = $this->formatter->formatAsAbbreviatedDayOfWeek($date) . ' '
            . $this->formatter->formatAsDayNumber($date) . ' '
            . $this->formatter->formatAsAbbreviatedMonthName($date);

        if (!DateComparison::isCurrentYear($date)) {
            $formattedDate .= ' ' . $this->formatter->formatAsYear($date);
        }
        return $formattedDate;
    }
}
