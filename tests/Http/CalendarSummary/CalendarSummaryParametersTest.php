<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\CalendarSummary;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeZone;
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
            'hidePast' => 'true',
            'timezone' => 'America/New_York',
        ], 'text/html'));

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
        // Which legacy names exist depends on the timezone database PHP uses: distributions that use the system
        // tzdata can leave them out (Ubuntu moved them to tzdata-legacy), so take one this PHP actually knows.
        $legacyTimezones = array_values(array_diff(
            DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),
            DateTimeZone::listIdentifiers()
        ));
        if ($legacyTimezones === []) {
            $this->markTestSkipped('This PHP knows no backwards compatible timezone names.');
        }

        $parameters = new CalendarSummaryParameters($this->request(['timezone' => $legacyTimezones[0]]));

        $this->assertSame($legacyTimezones[0], $parameters->timezone);
    }

    /**
     * @test
     */
    public function it_reads_hide_past_as_a_boolean(): void
    {
        $this->assertFalse((new CalendarSummaryParameters($this->request(['hidePast' => 'false'])))->hidePast);
        $this->assertTrue((new CalendarSummaryParameters($this->request(['hidePast' => '1'])))->hidePast);
    }

    /**
     * @test
     * @dataProvider provideAcceptHeaders
     */
    public function it_derives_the_type_from_the_accept_header(string $accept, string $expectedType): void
    {
        $parameters = new CalendarSummaryParameters($this->request([], $accept));

        $this->assertSame($expectedType, $parameters->format->getType());
    }

    public function provideAcceptHeaders(): array
    {
        return [
            'html' => ['text/html', 'html'],
            'plain text' => ['text/plain', 'text'],
            'html with parameters' => ['text/html; charset=utf-8', 'html'],
            'only the first media type counts' => ['text/plain; q=0.2, text/html', 'text'],
            'browser default' => ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'text'],
            'unsupported media type' => ['application/json', 'text'],
            'wildcard' => ['*/*', 'text'],
        ];
    }

    /**
     * @test
     */
    public function it_ignores_the_style_parameter_that_entry_api_only_supports_for_backward_compatibility(): void
    {
        $parameters = new CalendarSummaryParameters($this->request(['style' => 'html']));

        $this->assertSame('text', $parameters->format->getType());
    }

    private function request(array $queryParameters, ?string $accept = null): ApiRequest
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/events/1/calendar-summary')
            ->withQueryParams($queryParameters);

        return new ApiRequest($accept === null ? $request : $request->withHeader('Accept', $accept));
    }
}
