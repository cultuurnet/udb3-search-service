<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Single\MediumSinglePlainTextFormatter;

final readonly class MediumMultiplePlainTextFormatter implements MultipleFormatterInterface
{
    public function __construct(private Translator $translator, private bool $hidePast)
    {
    }

    public function format(Offer $offer): string
    {
        $subEvents = $offer->getSubEvents();
        $subEventSummaries = [];

        foreach ($subEvents as $key => $subEvent) {
            $formatter = new MediumSinglePlainTextFormatter($this->translator);

            if (!$this->hidePast || DateComparison::isInTheFuture($subEvent->getEndDate())) {
                $subEventSummaries[] = $formatter->format($subEvent);
            }
        }

        if (empty($subEventSummaries)) {
            return $this->translator->translate('event_concluded');
        }

        return implode(PHP_EOL, array_unique($subEventSummaries));
    }
}
