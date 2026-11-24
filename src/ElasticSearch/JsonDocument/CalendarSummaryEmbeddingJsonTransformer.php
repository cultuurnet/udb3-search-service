<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\CalendarSummary\CalendarFormatterInterface;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Locales;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class CalendarSummaryEmbeddingJsonTransformer implements JsonTransformer
{
    /**
     * @var CalendarSummaryFormat[]
     */
    private array $calendarSummaryFormats = [];

    private bool $hidePast = true;

    public function __construct(CalendarSummaryFormat ...$calendarSummaryFormats)
    {
        // A format requested twice would be merged recursively into a list instead of a string.
        foreach ($calendarSummaryFormats as $calendarSummaryFormat) {
            $key = $calendarSummaryFormat->getFormat() . '-' . $calendarSummaryFormat->getType();
            $this->calendarSummaryFormats[$key] = $calendarSummaryFormat;
        }
    }

    public function withHidePast(bool $hidePast): self
    {
        $clone = clone $this;
        $clone->hidePast = $hidePast;
        return $clone;
    }

    public function transform(array $original, array $draft = []): array
    {
        if ($this->calendarSummaryFormats === []) {
            return $draft;
        }

        $offer = Offer::fromJsonLd($original['originalEncodedJsonLd']);

        foreach ($this->calendarSummaryFormats as $calendarSummaryFormat) {
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

        foreach (Locales::BY_LANGUAGE as $language => $locale) {
            $calendarFormatter = $this->getCalendarFormatterByTypeAndLocale($calendarSummaryFormat->getType(), $locale);

            $calendarSummaries[$language][$calendarSummaryFormat->getType()] = [
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
                return new CalendarPlainTextFormatter($locale, $this->hidePast, 'Europe/Brussels');
            case 'html':
                return new CalendarHTMLFormatter($locale, $this->hidePast, 'Europe/Brussels');
            default:
                throw new UnsupportedParameterValue(
                    $calendarSummaryType . ' is not a supported calendar summary type'
                );
        }
    }
}
