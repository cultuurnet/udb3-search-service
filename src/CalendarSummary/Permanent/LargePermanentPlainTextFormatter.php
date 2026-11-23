<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;

final readonly class LargePermanentPlainTextFormatter implements PermanentFormatterInterface
{
    public function __construct(private Translator $translator)
    {
    }

    public function format(Offer $offer): string
    {
        if ($offer->getStatus()->getType() === 'Unavailable') {
            return ucfirst($this->translator->translate('cancelled'));
        }

        if ($offer->getStatus()->getType() === 'TemporarilyUnavailable') {
            return ucfirst($this->translator->translate('postponed'));
        }

        if (!$offer->getOpeningHours()->isEmpty()) {
            $weekScheme = PlainTextWeekSchemeFormatter::forOpeningHours($offer->getOpeningHours(), $this->translator)
                ->withChildcare()
                ->toString();

            // The adjusted days themselves are only listed in the extra large format, so
            // here their existence is only hinted at.
            if (DateComparison::withoutPastPeriods($offer->getAdjustedDays()) !== []) {
                $weekScheme .= PHP_EOL . ucfirst($this->translator->translate('adjusted_hours_notice'));
            }

            return $weekScheme . PHP_EOL;
        }

        return PlainTextSummaryBuilder::start($this->translator)
            ->alwaysOpen()
            ->startNewLine()
            ->toString();
    }
}
