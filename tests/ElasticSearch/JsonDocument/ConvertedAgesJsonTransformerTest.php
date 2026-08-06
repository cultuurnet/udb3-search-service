<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use PHPUnit\Framework\TestCase;

final class ConvertedAgesJsonTransformerTest extends TestCase
{
    private ConvertedAgesJsonTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ConvertedAgesJsonTransformer();
    }

    /**
     * @test
     */
    public function it_adds_a_converted_age_range_when_the_original_only_has_a_birthdate_range(): void
    {
        $from = [
            'typicalAgeRange' => ['gte' => 6, 'lte' => 7],
            'birthdateRange' => ['gte' => '2010-01-01', 'lte' => '2010-12-31'],
        ];

        $draft = [
            'birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-12-31'],
        ];

        $result = $this->transformer->transform($from, $draft);

        $this->assertSame('6-7', $result['typicalAgeRangeConverted']);
        $this->assertArrayNotHasKey('birthdateRangeConverted', $result);
    }

    /**
     * @test
     */
    public function it_adds_a_converted_birthdate_range_when_the_original_only_has_an_age_range(): void
    {
        $from = [
            'typicalAgeRange' => ['gte' => 6, 'lte' => 7],
            'birthdateRange' => ['gte' => '2009-04-23', 'lte' => '2011-04-22'],
        ];

        $draft = [
            'typicalAgeRange' => '6-7',
        ];

        $result = $this->transformer->transform($from, $draft);

        $this->assertSame(['from' => '2009-04-23', 'to' => '2011-04-22'], $result['birthdateRangeConverted']);
        $this->assertArrayNotHasKey('typicalAgeRangeConverted', $result);
    }

    /**
     * @test
     */
    public function it_adds_nothing_when_both_ranges_were_entered(): void
    {
        $from = [
            'typicalAgeRange' => ['gte' => 6, 'lte' => 7],
            'birthdateRange' => ['gte' => '2010-01-01', 'lte' => '2010-12-31'],
        ];

        $draft = [
            'typicalAgeRange' => '6-7',
            'birthdateRange' => ['from' => '2010-01-01', 'to' => '2010-12-31'],
        ];

        $result = $this->transformer->transform($from, $draft);

        $this->assertArrayNotHasKey('typicalAgeRangeConverted', $result);
        $this->assertArrayNotHasKey('birthdateRangeConverted', $result);
    }

    /**
     * @test
     * @dataProvider unboundedBirthdateRangeProvider
     */
    public function it_does_not_add_a_converted_birthdate_range_when_it_is_unbounded(
        string $typicalAgeRange,
        array $birthdateRange
    ): void {
        $from = ['birthdateRange' => $birthdateRange];
        $draft = ['typicalAgeRange' => $typicalAgeRange];

        $result = $this->transformer->transform($from, $draft);

        $this->assertArrayNotHasKey('birthdateRangeConverted', $result);
    }

    public function unboundedBirthdateRangeProvider(): array
    {
        return [
            'all ages ("-")' => ['-', ['gte' => null, 'lte' => null]],
            'no maximum age ("5-")' => ['5-', ['gte' => null, 'lte' => '2015-01-01']],
        ];
    }
}
