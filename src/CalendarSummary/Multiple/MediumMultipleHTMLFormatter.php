<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Single\MediumSingleHTMLFormatter;

final readonly class MediumMultipleHTMLFormatter implements MultipleFormatterInterface
{
    public function __construct(private Translator $translator, private bool $hidePast)
    {
    }

    public function format(Offer $offer): string
    {
        $subEvents = $offer->getSubEvents();
        $formatter = new MediumSingleHTMLFormatter($this->translator);

        $subEventSummaries = [];
        foreach ($subEvents as $subEvent) {
            if (!$this->hidePast || DateComparison::isInTheFuture($subEvent->getEndDate())) {
                $subEventSummaries[] = $formatter->format($subEvent);
            }
        }
        $subEventSummaries = array_unique($subEventSummaries);

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
