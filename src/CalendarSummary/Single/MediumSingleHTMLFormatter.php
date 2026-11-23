<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeInterface;

final readonly class MediumSingleHTMLFormatter implements SingleFormatterInterface
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
        $intlDateFrom = $this->formatter->formatAsFullDate($dateFrom);
        $intlDateDayFrom = $this->formatter->formatAsAbbreviatedDayOfWeek($dateFrom);

        $output = '<span class="cf-weekday cf-meta">' . ucfirst($intlDateDayFrom) . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-date">' . $intlDateFrom . '</span>';

        return $output;
    }

    private function formatMoreDays(DateTimeInterface $dateFrom, DateTimeInterface $dateEnd): string
    {
        $intlDateFrom = $this->formatter->formatAsFullDate($dateFrom);
        $intlDateDayFrom = $this->formatter->formatAsAbbreviatedDayOfWeek($dateFrom);

        $intlDateEnd = $this->formatter->formatAsFullDate($dateEnd);
        $intlDateDayEnd = $this->formatter->formatAsAbbreviatedDayOfWeek($dateEnd);

        $output = '<span class="cf-from cf-meta">' . ucfirst($this->translator->translate('from')) . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-weekday cf-meta">' . $intlDateDayFrom . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-date">' . $intlDateFrom . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-to cf-meta">' . $this->translator->translate('till') . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-weekday cf-meta">' . $intlDateDayEnd . '</span>';
        $output .= ' ';
        $output .= '<span class="cf-date">' . $intlDateEnd . '</span>';

        return $output;
    }
}
