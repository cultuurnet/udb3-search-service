<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\RelativeDateHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeInterface;

final class SmallMultipleHTMLFormatter implements MultipleFormatterInterface
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
        $intlDateFrom = $this->getIntlDate($dateFrom);
        $dayOfWeekFrom = $this->formatter->formatAsAbbreviatedDayOfWeek($dateFrom);

        $dateTo = $offer->getEndDate();
        $intlDateTo = $this->getIntlDate($dateTo);
        $dayOfWeekTo = $this->formatter->formatAsAbbreviatedDayOfWeek($dateTo);

        if (DateComparison::onSameDay($dateFrom, $dateTo)) {
            $output = $this->formatSameDay($dateFrom, $intlDateFrom);
        } else {
            $output = '<span class="cf-weekday cf-meta">' . ucfirst($dayOfWeekFrom) . '</span>'
                . ' '
                . '<span class="cf-date">' . $intlDateFrom . '</span> '
                . '<span class="cf-to cf-meta">-</span> '
                . '<span class="cf-weekday cf-meta">' . $dayOfWeekTo . '</span>'
                . ' '
                . '<span class="cf-date">' . $intlDateTo . '</span>';
        }

        $optionalAvailability = HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
            ->withBraces()
            ->toString();

        return trim($output . ' ' . $optionalAvailability);
    }

    private function formatSameDay(DateTimeInterface $dateFrom, string $intlDateFrom): string
    {
        $relativeDate = $this->getRelativeDate($dateFrom, $this->translator, $this->formatter);
        return $relativeDate ?? (
            '<span class="cf-weekday cf-meta">' . ucfirst($this->formatter->formatAsAbbreviatedDayOfWeek($dateFrom)) . '</span>' .
            ' ' .
            '<span class="cf-date">' . $intlDateFrom . '</span>'
        );
    }

    private function getIntlDate(\DateTimeImmutable $dateTime): string
    {
        $intlDate = $this->formatter->formatAsDayNumber($dateTime) .
            ' ' . $this->formatter->formatAsAbbreviatedMonthName($dateTime);
        if (!DateComparison::isCurrentYear($dateTime)) {
            $intlDate .= ' ' . $this->formatter->formatAsYear($dateTime);
        }
        return $intlDate;
    }
}
