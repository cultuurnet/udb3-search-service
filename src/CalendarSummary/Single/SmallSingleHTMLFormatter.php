<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\RelativeDateHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final class SmallSingleHTMLFormatter implements SingleFormatterInterface
{
    use RelativeDateHTMLFormatter;
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
        $dateFrom = $offer->getStartDate();
        $dateEnd = $offer->getEndDate();

        if (DateComparison::onSameDay($dateFrom, $dateEnd)) {
            $output = $this->formatSameDay($dateFrom);
        } else {
            $output = $this->formatMoreDays($dateFrom, $dateEnd);
        }

        $optionalAvailability = HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
            ->withBraces()
            ->toString();

        return trim($output . ' ' . $optionalAvailability);
    }

    private function formatSameDay(DateTimeInterface $dateFrom): string
    {
        $relativeDate = $this->getRelativeDate($dateFrom, $this->translator, $this->formatter);
        if (isset($relativeDate)) {
            return $relativeDate;
        }

        $dateFromWeekDay = $this->formatter->formatAsAbbreviatedDayOfWeek($dateFrom);
        $dateFromDay = $this->formatter->formatAsDayNumber($dateFrom);
        $dateFromMonth = $this->formatter->formatAsAbbreviatedMonthName($dateFrom);

        $formattedDate = '<span class="cf-weekday cf-meta">' . ucfirst($dateFromWeekDay) . '</span>'
            . ' '
            . '<span class="cf-date">' . ucfirst($dateFromDay) . '</span>'
            . ' '
            . '<span class="cf-month">' . $dateFromMonth . '</span>';

        if (!DateComparison::isCurrentYear($dateFrom)) {
            $formattedDate .= ' <span class="cf-year">' . $this->formatter->formatAsYear($dateFrom) . '</span>';
        }

        return $formattedDate;
    }

    private function formatMoreDays(DateTimeInterface $dateFrom, DateTimeInterface $dateEnd): string
    {
        $dateFromWeekDay = $this->formatter->formatAsAbbreviatedDayOfWeek($dateFrom);
        $dateFromDay = $this->formatter->formatAsDayNumber($dateFrom);
        $dateFromMonth = $this->formatter->formatAsAbbreviatedMonthName($dateFrom);

        $dateEndWeekDay = $this->formatter->formatAsAbbreviatedDayOfWeek($dateEnd);
        $dateEndDay = $this->formatter->formatAsDayNumber($dateEnd);
        $dateEndMonth = $this->formatter->formatAsAbbreviatedMonthName($dateEnd);

        $output = '<span class="cf-weekday cf-meta">' . ucfirst($dateFromWeekDay) . '</span>' ;
        $output .= ' ';
        $output .= '<span class="cf-date">' . $dateFromDay . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-month">' . $dateFromMonth . '</span>';
        if (!DateComparison::isCurrentYear($dateFrom)) {
            $output .= ' ';
            $output .= '<span class="cf-year">' . $this->formatter->formatAsYear($dateFrom) . '</span>';
        }
        $output .= ' ';
        $output .= '<span class="cf-to cf-meta">-</span>';
        $output .= ' ';
        $output .= '<span class="cf-weekday cf-meta">' . ucfirst($dateEndWeekDay) . '</span>' ;
        $output .= ' ';
        $output .= '<span class="cf-date">' . $dateEndDay . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-month">' . $dateEndMonth . '</span>';
        if (!DateComparison::isCurrentYear($dateEnd)) {
            $output .= ' ';
            $output .= '<span class="cf-year">' . $this->formatter->formatAsYear($dateEnd) . '</span>';
        }

        return $output;
    }
}
