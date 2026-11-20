<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

/**
 * Renders the adjusted days followed by the closed days as HTML.
 */
final readonly class HtmlDeviatingDaysFormatter
{
    private HtmlAdjustedDaysFormatter $adjustedDaysFormatter;

    private HtmlClosedDaysFormatter $closedDaysFormatter;

    public function __construct(Translator $translator)
    {
        $this->adjustedDaysFormatter = new HtmlAdjustedDaysFormatter($translator);
        $this->closedDaysFormatter = new HtmlClosedDaysFormatter($translator);
    }

    /**
     * Both formatters render a block of their own, so unlike the plain text
     * variant they need no separator in between. Empty when there is nothing
     * to show.
     *
     * @param string[] $closedDaysOfWeek the days of the week that are closed every week,
     *   listed among the closed days
     */
    public function format(Offer $offer, array $closedDaysOfWeek = []): string
    {
        return $this->adjustedDaysFormatter->format($offer->getAdjustedDays())
            . $this->closedDaysFormatter->format($offer->getClosedDays(), $closedDaysOfWeek);
    }
}
