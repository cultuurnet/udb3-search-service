<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Single\MediumSinglePlainTextFormatter;

final class MediumMultiplePlainTextFormatter implements MultipleFormatterInterface
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
