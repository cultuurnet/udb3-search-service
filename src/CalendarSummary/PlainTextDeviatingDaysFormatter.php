<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

/**
 * Renders the adjusted days followed by the closed days as plain text.
 */
final readonly class PlainTextDeviatingDaysFormatter
{
    private PlainTextAdjustedDaysFormatter $adjustedDaysFormatter;

    private PlainTextClosedDaysFormatter $closedDaysFormatter;

    public function __construct(Translator $translator)
    {
        $this->adjustedDaysFormatter = new PlainTextAdjustedDaysFormatter($translator);
        $this->closedDaysFormatter = new PlainTextClosedDaysFormatter($translator);
    }

    /**
     * The adjusted and closed days get some visual space, both from the opening
     * hours above them and from each other. Empty when there is nothing to show,
     * so the caller does not have to guard the leading blank line itself.
     *
     * @param string[] $closedDaysOfWeek the days of the week that are closed every week,
     *   listed among the closed days
     */
    public function format(Offer $offer, array $closedDaysOfWeek = []): string
    {
        $blocks = array_filter([
            $this->adjustedDaysFormatter->format($offer->getAdjustedDays()),
            $this->closedDaysFormatter->format($offer->getClosedDays(), $closedDaysOfWeek),
        ]);

        if (!$blocks) {
            return '';
        }

        $separator = PHP_EOL . PHP_EOL;

        return $separator . implode($separator, $blocks);
    }
}
