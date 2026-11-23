<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;

final readonly class ExtraSmallMultipleHTMLFormatter implements MultipleFormatterInterface
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
        $dateFrom = $offer->getStartDate();
        $dateTo = $offer->getEndDate();

        if (DateComparison::onSameDay($dateFrom, $dateTo)) {
            $output = '<span class="cf-date">' . $this->formatter->formatAsDayNumber($dateFrom) . '</span> ' .
                '<span class="cf-month">' . $this->formatter->formatAsAbbreviatedMonthName($dateFrom) . '</span>';
            if (!DateComparison::isCurrentYear($dateFrom)) {
                $output .= ' <span class="cf-year">' . $this->formatter->formatAsYear($dateFrom) . '</span>';
            }
        } else {
            $output = '<span class="cf-date">' . $this->formatter->formatAsDayNumber($dateFrom) . '</span> ' .
                '<span class="cf-month">' . $this->formatter->formatAsAbbreviatedMonthName($dateFrom) . '</span> ' .
                '<span class="cf-year">' . $this->formatter->formatAsYear($dateFrom) . '</span>' .
                ' - ' .
                '<span class="cf-date">' . $this->formatter->formatAsDayNumber($dateTo) . '</span> ' .
                '<span class="cf-month">' . $this->formatter->formatAsAbbreviatedMonthName($dateTo) . '</span>';
            if (!DateComparison::isCurrentYear($dateTo)) {
                $output .= ' <span class="cf-year">' . $this->formatter->formatAsYear($dateTo) . '</span>';
            }
        }

        $optionalAvailability = HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
            ->withBraces()
            ->toString();

        return trim($output . ' ' . $optionalAvailability);
    }
}
