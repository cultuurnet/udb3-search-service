<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\CalendarSummary;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class CalendarSummaryParametersTest extends TestCase
{
    /**
     * @test
     */
    public function it_defaults_to_a_large_dutch_text_summary_in_brussels(): void
    {
        $parameters = new CalendarSummaryParameters($this->request([]));

        $this->assertSame('nl_BE', $parameters->locale);
        $this->assertEquals(new CalendarSummaryFormat('text', 'lg'), $parameters->format);
        $this->assertFalse($parameters->hidePast);
        $this->assertSame('Europe/Brussels', $parameters->timezone);
    }

    /**
     * @test
     */
    public function it_reads_every_parameter(): void
    {
        $parameters = new CalendarSummaryParameters($this->request([
            'language' => 'fr',
            'size' => 'xs',
            'style' => 'html',
            'hidePast' => 'true',
            'timezone' => 'America/New_York',
        ]));

        $this->assertSame('fr_BE', $parameters->locale);
        $this->assertEquals(new CalendarSummaryFormat('html', 'xs'), $parameters->format);
        $this->assertTrue($parameters->hidePast);
        $this->assertSame('America/New_York', $parameters->timezone);
    }

    /**
     * @test
     * @dataProvider provideLanguages
     */
    public function it_maps_a_language_to_its_locale(string $language, string $expectedLocale): void
    {
        $parameters = new CalendarSummaryParameters($this->request(['language' => $language]));

        $this->assertSame($expectedLocale, $parameters->locale);
    }

    public function provideLanguages(): array
    {
        return [
            'nl' => ['nl', 'nl_BE'],
            'fr' => ['fr', 'fr_BE'],
            'de' => ['de', 'de'],
            'en' => ['en', 'en'],
        ];
    }

    /**
     * @test
     * @dataProvider provideInvalidParameters
     */
    public function it_refuses_an_invalid_value(array $queryParameters): void
    {
        $this->expectException(UnsupportedParameterValue::class);

        new CalendarSummaryParameters($this->request($queryParameters));
    }

    public function provideInvalidParameters(): array
    {
        return [
            'unknown language' => [['language' => 'es']],
            'locale instead of language' => [['language' => 'nl_BE']],
            'unknown size' => [['size' => 'xxl']],
            'unknown style' => [['style' => 'json']],
            'multiple languages' => [['language' => ['nl', 'fr']]],
            'multiple timezones' => [['timezone' => ['Europe/Brussels', 'UTC']]],
        ];
    }

    /**
     * @test
     */
    public function it_falls_back_to_brussels_for_an_unknown_timezone(): void
    {
        $parameters = new CalendarSummaryParameters($this->request(['timezone' => 'Foo/Bar']));

        $this->assertSame('Europe/Brussels', $parameters->timezone);
    }

    /**
     * @test
     */
    public function it_keeps_a_backwards_compatible_timezone(): void
    {
        $parameters = new CalendarSummaryParameters($this->request(['timezone' => 'US/Eastern']));

        $this->assertSame('US/Eastern', $parameters->timezone);
    }

    /**
     * @test
     */
    public function it_reads_hide_past_as_a_boolean(): void
    {
        $this->assertFalse((new CalendarSummaryParameters($this->request(['hidePast' => 'false'])))->hidePast);
        $this->assertTrue((new CalendarSummaryParameters($this->request(['hidePast' => '1'])))->hidePast);
    }

    private function request(array $queryParameters): ApiRequest
    {
        return new ApiRequest(
            (new ServerRequestFactory())
                ->createServerRequest('GET', '/calendar-summary/events/1')
                ->withQueryParams($queryParameters)
        );
    }
}
