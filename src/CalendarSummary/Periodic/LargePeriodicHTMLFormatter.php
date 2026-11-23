<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlSummaryFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeImmutable;

final readonly class LargePeriodicHTMLFormatter implements PeriodicFormatterInterface
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
        $optionalAvailability = HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
            ->withBraces()
            ->toString();

        $output = $this->generateDates(
            $offer->getStartDate(),
            $offer->getEndDate(),
            $optionalAvailability
        );

        if (!$offer->getOpeningHours()->isEmpty()) {
            $weekScheme = HtmlWeekSchemeFormatter::forOpeningHours($offer->getOpeningHours(), $this->translator)
                ->withHeading()
                ->withChildcareInNestedList();

            // The adjusted days themselves are only listed in the extra large format, so
            // here their existence is only hinted at.
            if (DateComparison::withoutPastPeriods($offer->getAdjustedDays()) !== []) {
                $weekScheme = $weekScheme->withAdjustedHoursNotice();
            }

            $output .= $weekScheme->toString();
        }

        return trim(HtmlSummaryFormatter::format($output));
    }

    private function generateDates(DateTimeImmutable $dateFrom, DateTimeImmutable $dateTo, string $optionalStatus): string
    {
        $intlDateFrom = $this->formatter->formatAsFullDate($dateFrom);
        $intlDateTo = $this->formatter->formatAsFullDate($dateTo);

        return '<p class="cf-period">'
            . '<span class="cf-weekday cf-meta">' . $this->formatter->formatAsDayOfWeek($dateFrom) . '</span>'
            . '<time itemprop="startDate" datetime="' . $dateFrom->format('Y-m-d') . '">'
            . '<span class="cf-date">' . $intlDateFrom . '</span> </time>'
            . '<span class="cf-to cf-meta">' . $this->translator->translate('to') . '</span>'
            . '<span class="cf-weekday cf-meta">' . $this->formatter->formatAsDayOfWeek($dateTo) . '</span>'
            . '<time itemprop="endDate" datetime="' . $dateTo->format('Y-m-d') . '">'
            . '<span class="cf-date">' . $intlDateTo . '</span> </time>'
            . $optionalStatus
            . '</p>';
    }
}
