<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use PHPUnit\Framework\TestCase;

final class ResultTransformerFactoryTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../ElasticSearch/JsonDocument/data/event';

    /**
     * @test
     */
    public function it_returns_only_the_minimal_info_when_not_embedded(): void
    {
        $result = $this->transform($this->fixture('indexed-with-typical-age-range.json'), false);

        $this->assertSame(
            [
                '@id' => 'http://udb-silex.dev/event/23017cb7-e515-47b4-87c4-780735acc942',
                '@type' => 'Event',
            ],
            $result
        );
    }

    /**
     * @test
     */
    public function it_embeds_the_full_document_when_embedded(): void
    {
        $result = $this->transform($this->fixture('indexed-with-typical-age-range.json'));

        $this->assertSame(['nl' => 'Punkfest'], $result['name']);
        $this->assertArrayHasKey('regions', $result);
    }

    /**
     * @test
     */
    public function it_exposes_a_converted_age_range_for_a_birthdate_entered_event(): void
    {
        $result = $this->transform($this->fixture('indexed-with-birthdate-range.json'));

        $this->assertSame('6-7', $result['typicalAgeRangeConverted']);
        $this->assertArrayNotHasKey('birthdateRangeConverted', $result);
    }

    /**
     * @test
     */
    public function it_exposes_a_converted_birthdate_range_for_an_age_entered_event(): void
    {
        $result = $this->transform($this->fixture('indexed-with-typical-age-range.json'));

        $this->assertSame(['from' => '2009-04-23', 'to' => '2011-04-22'], $result['birthdateRangeConverted']);
        $this->assertArrayNotHasKey('typicalAgeRangeConverted', $result);
    }

    /**
     * @test
     */
    public function it_exposes_no_converted_ranges_for_an_all_ages_event(): void
    {
        $result = $this->transform($this->fixture('indexed-for-all-ages.json'));

        $this->assertArrayNotHasKey('typicalAgeRangeConverted', $result);
        $this->assertArrayNotHasKey('birthdateRangeConverted', $result);
    }

    /**
     * @test
     */
    public function it_strips_uitpas_prices_by_default(): void
    {
        $result = $this->transform($this->withPriceInfo(), true, false);

        $this->assertSame(['base'], array_column($result['priceInfo'], 'category'));
    }

    /**
     * @test
     */
    public function it_keeps_uitpas_prices_when_requested(): void
    {
        $result = $this->transform($this->withPriceInfo(), true, true);

        $this->assertSame(['base', 'uitpas'], array_column($result['priceInfo'], 'category'));
    }

    /**
     * @test
     */
    public function it_embeds_no_calendar_summary_when_none_is_requested(): void
    {
        $result = $this->transform($this->permanentOffer());

        $this->assertArrayNotHasKey('calendarSummary', $result);
    }

    /**
     * @test
     * @dataProvider providesEmbedToggles
     */
    public function it_embeds_the_requested_calendar_summaries(bool $embedded): void
    {
        $result = ResultTransformerFactory::create(
            $embedded,
            false,
            true,
            CalendarSummaryFormat::fromCombinedParameter('md-text'),
            CalendarSummaryFormat::fromCombinedParameter('xs-html')
        )->transform($this->permanentOffer(), []);

        $this->assertSame('Alle dagen open', $result['calendarSummary']['nl']['text']['md']);
        $this->assertSame(
            '<p class="cf-openinghours">Alle dagen open</p>',
            $result['calendarSummary']['nl']['html']['xs']
        );
        $this->assertSame(['nl', 'fr', 'de', 'en'], array_keys($result['calendarSummary']));
    }

    public function providesEmbedToggles(): array
    {
        return [
            'embedded' => [true],
            'minimal' => [false],
        ];
    }

    private function transform(array $source, bool $embedded = true, bool $embedUitpasPrices = false): array
    {
        return ResultTransformerFactory::create($embedded, $embedUitpasPrices)->transform($source, []);
    }

    private function permanentOffer(): array
    {
        return [
            '@id' => 'events/1',
            '@type' => 'Event',
            'originalEncodedJsonLd' => Json::encode([
                '@id' => 'events/1',
                '@type' => 'Event',
                '@context' => '/contexts/event',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
                'calendarType' => 'permanent',
            ]),
        ];
    }

    private function fixture(string $name): array
    {
        return Json::decodeAssociatively(FileReader::read(self::FIXTURES . '/' . $name));
    }

    private function withPriceInfo(): array
    {
        return [
            '@id' => 'events/1',
            '@type' => 'Event',
            'originalEncodedJsonLd' => Json::encode([
                '@id' => 'events/1',
                '@type' => 'Event',
                'priceInfo' => [
                    ['category' => 'base', 'price' => 10, 'priceCurrency' => 'EUR'],
                    ['category' => 'uitpas', 'price' => 1.5, 'priceCurrency' => 'EUR'],
                ],
            ]),
        ];
    }
}
