<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
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

    private function transform(array $source, bool $embedded = true, bool $embedUitpasPrices = false): array
    {
        return ResultTransformerFactory::create($embedded, $embedUitpasPrices)->transform($source, []);
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
