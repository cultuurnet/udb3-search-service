<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

final class MediumPeriodicHTMLFormatter implements PeriodicFormatterInterface
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
        $dateFrom = $offer->getStartDate();
        $intlDateFrom = $this->formatter->formatAsFullDate($dateFrom);
        $intlDateFromDay = $this->formatter->formatAsAbbreviatedDayOfWeek($dateFrom);

        $dateTo = $offer->getEndDate();
        $intlDateTo = $this->formatter->formatAsFullDate($dateTo);
        $intlDateDayTo = $this->formatter->formatAsAbbreviatedDayOfWeek($dateTo);

        if ($intlDateFrom == $intlDateTo) {
            $output = '<span class="cf-weekday cf-meta">' . ucfirst($intlDateFromDay) . '</span>'
                . ' '
                . '<span class="cf-date">' . $intlDateFrom . '</span>';
        } else {
            $output = '<span class="cf-from cf-meta">' . ucfirst($this->translator->translate('from')) . '</span> '
                . '<span class="cf-weekday cf-meta">' . $intlDateFromDay . '</span> '
                . '<span class="cf-date">' . $intlDateFrom . '</span> '
                . '<span class="cf-to cf-meta">' . $this->translator->translate('till') . '</span> '
                . '<span class="cf-weekday cf-meta">' . $intlDateDayTo . '</span> '
                . '<span class="cf-date">' . $intlDateTo . '</span>';
        }

        $optionalAvailability = HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
            ->withBraces()
            ->toString();

        return trim($output . ' ' . $optionalAvailability);
    }
}
