<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextDeviatingDaysFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextWeekSchemeFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;

final readonly class ExtraLargePermanentPlainTextFormatter implements PermanentFormatterInterface
{
    private PlainTextDeviatingDaysFormatter $deviatingDaysFormatter;

    public function __construct(private Translator $translator)
    {
        $this->deviatingDaysFormatter = new PlainTextDeviatingDaysFormatter($this->translator);
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
            $output = PlainTextWeekSchemeFormatter::forOpeningHours($offer->getOpeningHours(), $this->translator)
                ->withChildcare()
                ->withoutClosedDays()
                ->toString();
        } else {
            $output = PlainTextSummaryBuilder::start($this->translator)
                ->alwaysOpen()
                ->toString();
        }

        // The days without opening hours are listed among the closed days instead of in
        // the week scheme.
        $deviatingDays = $this->deviatingDaysFormatter->format(
            $offer,
            $offer->getOpeningHours()->closedDaysOfWeek()
        );

        return $output . $deviatingDays . PHP_EOL;
    }
}
