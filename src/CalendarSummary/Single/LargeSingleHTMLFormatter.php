<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use DateTime;
use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlChildcareFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final readonly class LargeSingleHTMLFormatter implements SingleFormatterInterface
{
    private DateFormatter $formatter;

    private Translator $translator;

    /**
     * @param bool $childcareInNestedList renders the childcare as a nested list instead of
     *                                    inline, for the sub-events of a multiple calendar
     */
    public function __construct(Translator $translator, private bool $childcareInNestedList = false)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    public function format(Offer $offer): string
    {
        $dateFrom = $offer->getStartDate();
        $dateEnd = $offer->getEndDate();

        if (DateComparison::onSameDay($dateFrom, $dateEnd)) {
            $output = $this->formatSameDay($dateFrom, $dateEnd);
        } else {
            $output = $this->formatMoreDays($dateFrom, $dateEnd);
        }

        $optionalAvailability = HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
            ->withBraces()
            ->toString();

        $optionalChildcare = $this->formatChildcare($offer);

        // The availability stays the last inline element, but a nested list has to close
        // the summary because it is a block on its own.
        $parts = $this->childcareInNestedList
            ? [$output, $optionalAvailability, $optionalChildcare]
            : [$output, $optionalChildcare, $optionalAvailability];

        return implode(' ', array_filter($parts));
    }

    private function formatChildcare(Offer $offer): string
    {
        $formatter = HtmlChildcareFormatter::forOffer($offer, $this->translator);

        if ($this->childcareInNestedList) {
            $formatter = $formatter->asNestedList();
        }

        return $formatter->toString();
    }

    private function formatSameDay(DateTimeInterface $dateFrom, DateTimeInterface $dateEnd): string
    {
        $intlDateFrom = $this->formatter->formatAsFullDate($dateFrom);
        $intlWeekDayFrom = $this->formatter->formatAsDayOfWeek($dateFrom);
        $intlStartTimeFrom = $this->formatter->formatAsTime($dateFrom);
        $intlEndTimeEnd = $this->formatter->formatAsTime($dateEnd);

        if ($intlStartTimeFrom === '00:00' && $intlEndTimeEnd === '23:59') {
            return '<time itemprop="startDate" datetime="' . $dateFrom->format(DateTime::ATOM) . '">'
                . '<span class="cf-weekday cf-meta">' . ucfirst($intlWeekDayFrom) . '</span>'
                . ' '
                . '<span class="cf-date">' . $intlDateFrom . '</span>'
                . '</time>';
        }

        if ($intlStartTimeFrom == $intlEndTimeEnd) {
            return '<time itemprop="startDate" datetime="' . $dateFrom->format(DateTime::ATOM) . '">'
                . '<span class="cf-weekday cf-meta">' . ucfirst($intlWeekDayFrom) . '</span>'
                . ' '
                . '<span class="cf-date">' . $intlDateFrom . '</span>'
                . ' '
                . '<span class="cf-from cf-meta">' . $this->translator->translate('at') . '</span>'
                . ' '
                . '<span class="cf-time">' . $intlStartTimeFrom . '</span>'
                . '</time>';
        }

        return '<time itemprop="startDate" datetime="' . $dateFrom->format(DateTime::ATOM) . '">'
            . '<span class="cf-weekday cf-meta">' . ucfirst($intlWeekDayFrom) . '</span>'
            . ' '
            . '<span class="cf-date">' . $intlDateFrom . '</span>'
            . ' '
            . '<span class="cf-from cf-meta">' . $this->translator->translate('from_hour') . '</span>'
            . ' '
            . '<span class="cf-time">' . $intlStartTimeFrom . '</span>'
            . '</time>'
            . ' '
            . '<span class="cf-to cf-meta">' . $this->translator->translate('till_hour') . '</span>'
            . ' '
            . '<time itemprop="endDate" datetime="' . $dateEnd->format(DateTime::ATOM) . '">'
            . '<span class="cf-time">' . $intlEndTimeEnd . '</span>'
            . '</time>';
    }

    private function formatMoreDays(DateTimeInterface $dateFrom, DateTimeInterface $dateEnd): string
    {
        $intlDateFrom = $this->formatter->formatAsFullDate($dateFrom);
        $intlWeekDayFrom = $this->formatter->formatAsDayOfWeek($dateFrom);
        $intlStartTimeFrom = $this->formatter->formatAsTime($dateFrom);

        $intlDateEnd = $this->formatter->formatAsFullDate($dateEnd);
        $intlWeekDayEnd = $this->formatter->formatAsDayOfWeek($dateEnd);
        $intlEndTimeEnd = $this->formatter->formatAsTime($dateEnd);

        $output = '<time itemprop="startDate" datetime="' . $dateFrom->format(DateTime::ATOM) . '">';
        $output .= '<span class="cf-from cf-meta">' . ucfirst($this->translator->translate('from')) . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-weekday cf-meta">' . $intlWeekDayFrom . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-date">' . $intlDateFrom . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-at cf-meta">' . $this->translator->translate('at') . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-time">' . $intlStartTimeFrom . '</span>';
        $output .= '</time>';
        $output .= ' ';
        $output .= '<span class="cf-to cf-meta">' . $this->translator->translate('till_included') . '</span>';
        $output .= ' ';
        $output .= '<time itemprop="endDate" datetime="' . $dateEnd->format(DateTime::ATOM) . '">';
        $output .= '<span class="cf-weekday cf-meta">' . $intlWeekDayEnd . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-date">' . $intlDateEnd . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-at cf-meta">' . $this->translator->translate('at') . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-time">' . $intlEndTimeEnd . '</span>';
        $output .= '</time>';

        return $output;
    }
}
