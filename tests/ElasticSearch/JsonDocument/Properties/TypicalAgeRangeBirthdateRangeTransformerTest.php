<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

final class TypicalAgeRangeBirthdateRangeTransformerTest extends TestCase
{
    private TypicalAgeRangeBirthdateRangeTransformer $transformer;

    protected function setUp(): void
    {
        // A person born in 2020 is aged 5-6 as of this date.
        Chronos::setTestNow(Chronos::createFromFormat(DateTimeInterface::ATOM, '2026-06-29T12:00:00+02:00'));

        $this->transformer = new TypicalAgeRangeBirthdateRangeTransformer();
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow(null);
    }

    /**
     * @test
     */
    public function it_derives_a_birthdate_range_from_a_bounded_age_range(): void
    {
        $from = ['typicalAgeRange' => '5-6'];

        $expected = [
            'birthdateRange' => [
                'lte' => '2021-06-29',
                'gte' => '2019-06-30',
            ],
        ];

        $this->assertEquals($expected, $this->transformer->transform($from, []));
    }

    /**
     * @test
     */
    public function it_only_sets_a_lower_bound_for_an_open_ended_age_range(): void
    {
        $from = ['typicalAgeRange' => '12-'];

        $expected = [
            'birthdateRange' => [
                'lte' => '2014-06-29',
            ],
        ];

        $this->assertEquals($expected, $this->transformer->transform($from, []));
    }

    /**
     * @test
     */
    public function it_does_not_overwrite_an_explicit_birthdate_range(): void
    {
        $from = ['typicalAgeRange' => '5-6'];
        $draft = ['birthdateRange' => ['gte' => '2020-01-01', 'lte' => '2020-12-31']];

        $this->assertSame($draft, $this->transformer->transform($from, $draft));
    }

    /**
     * @test
     */
    public function it_does_nothing_for_an_all_ages_range(): void
    {
        $from = ['typicalAgeRange' => '-'];
        $draft = ['name' => 'unchanged'];

        $this->assertSame($draft, $this->transformer->transform($from, $draft));
    }

    /**
     * @test
     */
    public function it_does_nothing_when_typical_age_range_is_absent(): void
    {
        $draft = ['name' => 'unchanged'];

        $this->assertSame($draft, $this->transformer->transform([], $draft));
    }
}
