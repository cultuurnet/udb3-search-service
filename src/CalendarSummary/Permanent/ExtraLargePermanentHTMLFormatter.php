<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlDeviatingDaysFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlSummaryFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

final class ExtraLargePermanentHTMLFormatter implements PermanentFormatterInterface
{
    private Translator $translator;

    private HtmlDeviatingDaysFormatter $deviatingDaysFormatter;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
        $this->deviatingDaysFormatter = new HtmlDeviatingDaysFormatter($translator);
    }

    public function format(Offer $offer): string
    {
        if (!$offer->isAvailable()) {
            return HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
                ->withElement('p')
                ->withoutBraces()
                ->capitalize()
                ->toString();
        }

        if (!$offer->getOpeningHours()->isEmpty()) {
            $output = HtmlWeekSchemeFormatter::forOpeningHours($offer->getOpeningHours(), $this->translator)
                ->inWeekOrder()
                ->withChildcare()
                ->toString();
        } else {
            $output = '<p class="cf-openinghours">'
                . ucfirst($this->translator->translate('open_every_day'))
                . '</p>';
        }

        // The days without opening hours are listed among the closed days instead of in
        // the week scheme.
        $output .= $this->deviatingDaysFormatter->format(
            $offer,
            $offer->getOpeningHours()->closedDaysOfWeek()
        );

        return HtmlSummaryFormatter::format($output);
    }
}
