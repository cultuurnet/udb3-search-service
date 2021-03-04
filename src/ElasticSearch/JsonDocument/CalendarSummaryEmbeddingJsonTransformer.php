<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;

class CalendarSummaryEmbeddingJsonTransformer implements JsonTransformer
{
    /**
     * @var CalendarSummaryFormat[]
     */
    private $calendarSummaryFormats;

    public function __construct(CalendarSummaryFormat ...$calendarSummaryFormats)
    {
        $this->calendarSummaryFormats = $calendarSummaryFormats;
    }

    public function transform(array $original, array $draft = []): array
    {
        foreach ($this->calendarSummaryFormats as $calendarSummaryFormat) {
            $original = $this->embedCalendarSummary($original, $calendarSummaryFormat);
        }

        return $original;
    }

    private function embedCalendarSummary(array $original, CalendarSummaryFormat $calendarSummaryFormat): array
    {
        $calendarSummary = [
            $calendarSummaryFormat->getType() => [
                $calendarSummaryFormat->getFormat() => '',
            ]
        ];

        return array_merge_recursive(
            $original,
            [
                'calendarSummary' => $calendarSummary
            ]
        );
    }
}
