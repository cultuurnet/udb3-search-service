<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Single\LargeSinglePlainTextFormatter;

final class LargeMultiplePlainTextFormatter implements MultipleFormatterInterface
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var bool
     */
    private $hidePast;

    public function __construct(Translator $translator, bool $hidePastDates)
    {
        $this->translator = $translator;
        $this->hidePast = $hidePastDates;
    }

    public function format(Offer $offer): string
    {
        $subEvents = [];
        foreach ($offer->getSubEvents() as $subEvent) {
            if (!$this->hidePast || DateComparison::isInTheFuture($subEvent->getEndDate())) {
                $subEvents[] = $subEvent;
            }
        }

        // Every sub-event already is a line of its own, so its childcare gets one too.
        $formatter = new LargeSinglePlainTextFormatter($this->translator, true);

        $subEventSummaries = [];
        foreach ($subEvents as $subEvent) {
            $subEventSummaries[] = $formatter->format($subEvent);
        }

        if (empty($subEventSummaries)) {
            return $this->translator->translate('event_concluded');
        }

        return implode(PHP_EOL, $subEventSummaries);
    }
}
