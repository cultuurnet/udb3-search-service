<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final readonly class SmallPeriodicHTMLFormatter implements PeriodicFormatterInterface
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
        $startDate = $startDate->setTime(0, 0, 1);

        if (DateComparison::isInTheFuture($startDate)) {
            $output = $this->formatNotStarted($startDate);
        } else {
            $endDate = $offer->getEndDate();
            $output = $this->formatStarted($endDate);
        }

        $optionalAvailability = HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
            ->withBraces()
            ->toString();

        return trim($output . ' ' . $optionalAvailability);
    }

    private function formatStarted(DateTimeInterface $endDate): string
    {
        return
            '<span class="to meta">' . ucfirst($this->translator->translate('till')) . '</span> ' .
            $this->formatDate($endDate);
    }

    private function formatNotStarted(DateTimeInterface $startDate): string
    {
        return
            '<span class="from meta">' . ucfirst($this->translator->translate('from_period')) . '</span> ' .
            $this->formatDate($startDate);
    }

    private function formatDate(DateTimeInterface $date): string
    {
        $formattedDate = '<span class="cf-days">' . $this->formatter->formatAsAbbreviatedDayOfWeek($date) . '</span> ' .
            '<span class="cf-date">' . $this->formatter->formatAsDayNumber($date) . '</span> ' .
            '<span class="cf-month">' . $this->formatter->formatAsAbbreviatedMonthName($date) . '</span>';

        if (!DateComparison::isCurrentYear($date)) {
            $formattedDate .= ' <span class="cf-year">' . $this->formatter->formatAsYear($date) . '</span>';
        }
        return $formattedDate;
    }
}
