<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\CalendarSummaryV3\CalendarFormatterInterface;
use CultuurNet\CalendarSummaryV3\CalendarHTMLFormatter;
use CultuurNet\CalendarSummaryV3\CalendarPlainTextFormatter;
use CultuurNet\CalendarSummaryV3\Offer\Offer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

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
            $offer = Offer::fromJsonLd($original['originalEncodedJsonLd']);
            $calendarSummary = $this->getCalendarSummaryData($offer, $calendarSummaryFormat);
            $draft = array_merge_recursive(
                $draft,
                [
                    'calendarSummary' => $calendarSummary,
                ]
            );
        }

        return $draft;
    }

    private function getCalendarSummaryData(Offer $offer, CalendarSummaryFormat $calendarSummaryFormat): array
    {
        $calendarFormatter = $this->getCalendarFormatterByType($calendarSummaryFormat->getType());

        return [
            $calendarSummaryFormat->getType() => [
                $calendarSummaryFormat->getFormat() => trim(
                    $calendarFormatter->format(
                        $offer,
                        $calendarSummaryFormat->getFormat()
                    )
                ),
            ],
        ];
    }

    private function getCalendarFormatterByType(string $calendarSummaryType): CalendarFormatterInterface
    {
        switch ($calendarSummaryType) {
            case 'text':
                return new CalendarPlainTextFormatter('nl_BE', true, 'Europe/Brussels');
            case 'html':
                return new CalendarHTMLFormatter('nl_BE', true, 'Europe/Brussels');
            default:
                throw new UnsupportedParameterValue(
                    $calendarSummaryType . ' is not a supported calendar summary type'
                );
        }
    }
}
