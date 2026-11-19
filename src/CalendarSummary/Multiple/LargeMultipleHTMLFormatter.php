<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Single\LargeSingleHTMLFormatter;

final readonly class LargeMultipleHTMLFormatter implements MultipleFormatterInterface
{
    public function __construct(private Translator $translator, private bool $hidePastDates)
    {
    }

    public function format(Offer $offer): string
    {
        $subEvents = [];
        foreach ($offer->getSubEvents() as $subEvent) {
            if (!$this->hidePast || DateComparison::isInTheFuture($subEvent->getEndDate())) {
                $subEvents[] = $subEvent;
            }
        }

        // Every sub-event gets its own list item, so its childcare is nested inside of it.
        $formatter = new LargeSingleHTMLFormatter($this->translator, true);

        $subEventSummaries = [];
        foreach ($subEvents as $subEvent) {
            $subEventSummaries[] = $formatter->format($subEvent);
        }

        if (empty($subEventSummaries)) {
            return '<span>' . $this->translator->translate('event_concluded') . '</span>';
        }

        $output = '<ul class="cnw-event-date-info">';
        foreach ($subEventSummaries as $subEventSummary) {
            $output .= '<li>' . $subEventSummary . '</li>';
        }
        $output .= '</ul>';

        return $output;
    }
}
