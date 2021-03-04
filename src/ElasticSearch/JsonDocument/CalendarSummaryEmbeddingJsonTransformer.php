<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\CalendarSummaryV3\CalendarFormatterInterface;
use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use InvalidArgumentException;

final class CalendarSummaryEmbeddingJsonTransformer implements JsonTransformer
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
        $calendarFormatter = $this->getCalendarFormatterByType($calendarSummaryFormat->getType());
        $offer = Offer::fromJsonLd(json_encode($original));

        $calendarSummary = [
            $calendarSummaryFormat->getType() => [
                $calendarSummaryFormat->getFormat() => trim(
                    $calendarFormatter->format(
                            $offer,
                            $calendarSummaryFormat->getFormat()
                        )
                ),
            ],
        ];

        return array_merge_recursive(
            $original,
            [
                'calendarSummary' => $calendarSummary,
            ]
        );
    }

    private function getCalendarFormatterByType(string $calendarSummaryType): CalendarFormatterInterface
    {
        switch ($calendarSummaryType) {
            case 'text':
                return new CalendarPlainTextFormatter('nl_BE', true, 'Europe/Brussels');
            case 'html':
                return new CalendarHTMLFormatter('nl_BE', true, 'Europe/Brussels');
            default:
                throw new InvalidArgumentException('No calendar formatter configured for type ' . $calendarSummaryType);
        }
    }
}
