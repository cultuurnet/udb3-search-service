<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\CalendarSummary\CalendarFormatterInterface;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class CalendarSummaryEmbeddingJsonTransformer implements JsonTransformer
{
    private const LOCALES = [
        'nl_BE',
        'fr_BE',
        'de',
        'en',
    ];

    /**
     * @var CalendarSummaryFormat[]
     */
    private array $calendarSummaryFormats = [];

    public function __construct(CalendarSummaryFormat ...$calendarSummaryFormats)
    {
        // A format requested twice would be merged recursively into a list instead of a string.
        foreach ($calendarSummaryFormats as $calendarSummaryFormat) {
            $key = $calendarSummaryFormat->getFormat() . '-' . $calendarSummaryFormat->getType();
            $this->calendarSummaryFormats[$key] = $calendarSummaryFormat;
        }
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

    /**
     * This methods returns an associative array with following indexes:
     *  [language][type][format], for example [nl][text][sm] or [fr][html][md]
     * @return string[][][]
     */
    private function getCalendarSummaryData(Offer $offer, CalendarSummaryFormat $calendarSummaryFormat): array
    {
        $calendarSummaries = [];

        foreach (self::LOCALES as $locale) {
            $calendarFormatter = $this->getCalendarFormatterByTypeAndLocale($calendarSummaryFormat->getType(), $locale);

            $calendarSummaries[substr($locale, 0, 2)][$calendarSummaryFormat->getType()] = [
                $calendarSummaryFormat->getFormat() => trim(
                    $calendarFormatter->format(
                        $offer,
                        $calendarSummaryFormat->getFormat()
                    )
                ),
            ];
        }

        return $calendarSummaries;
    }

    private function getCalendarFormatterByTypeAndLocale(string $calendarSummaryType, string $locale): CalendarFormatterInterface
    {
        switch ($calendarSummaryType) {
            case 'text':
                return new CalendarPlainTextFormatter($locale, true, 'Europe/Brussels');
            case 'html':
                return new CalendarHTMLFormatter($locale, true, 'Europe/Brussels');
            default:
                throw new UnsupportedParameterValue(
                    $calendarSummaryType . ' is not a supported calendar summary type'
                );
        }
    }
}
