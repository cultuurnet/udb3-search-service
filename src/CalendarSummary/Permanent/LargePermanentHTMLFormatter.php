<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlSummaryFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

final class LargePermanentHTMLFormatter implements PermanentFormatterInterface
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
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
            $weekScheme = HtmlWeekSchemeFormatter::forOpeningHours($offer->getOpeningHours(), $this->translator)
                ->withEveryDayOfTheWeek()
                ->withChildcareInNestedList();

            // The adjusted days themselves are only listed in the extra large format, so
            // here their existence is only hinted at.
            if (DateComparison::withoutPastPeriods($offer->getAdjustedDays()) !== []) {
                $weekScheme = $weekScheme->withAdjustedHoursNotice();
            }

            return HtmlSummaryFormatter::format($weekScheme->toString());
        }

        return HtmlSummaryFormatter::format(
            '<p class="cf-openinghours">'
            . ucfirst($this->translator->translate('open_every_day'))
            . '</p>'
        );
    }
}
