<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\TestCase;

final class CalendarSummaryFormatTest extends TestCase
{
    /**
     * @test
     * @dataProvider providesValidParameters
     */
    public function it_can_parse_valid__parameters(
        string $parameter,
        string $type,
        string $format
    ): void {
        $this->assertEquals(
            new CalendarSummaryFormat($type, $format),
            CalendarSummaryFormat::fromCombinedParameter($parameter)
        );
    }

    public function providesValidParameters(): array
    {
        return [
            ['xs-text', 'text', 'xs'],
            ['sm-text', 'text', 'sm'],
            ['md-text', 'text', 'md'],
            ['lg-text', 'text', 'lg'],
            ['xl-text', 'text', 'xl'],
            ['xs-html', 'html', 'xs'],
            ['sm-html', 'html', 'sm'],
            ['md-html', 'html', 'md'],
            ['lg-html', 'html', 'lg'],
            ['xl-html', 'html', 'xl'],
        ];
    }

    /**
     * @test
     * @dataProvider providesInvalidParameters
     */
    public function it_throws_on_an_invalid_parameter(string $parameter, string $message): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage($message);

        CalendarSummaryFormat::fromCombinedParameter($parameter);
    }

    public function providesInvalidParameters(): array
    {
        return [
            'unknown type' => ['md-pdf', 'Invalid type: pdf. Use one of: text,html'],
            'unknown format' => ['xxl-text', 'Invalid format: xxl. Use one of: xs,sm,md,lg,xl'],
            'missing type' => ['md', 'Invalid calendar summary: md. Use the format {format}-{type}, e.g. md-text'],
            'too many parts' => ['md-text-html', 'Invalid calendar summary: md-text-html. Use the format {format}-{type}, e.g. md-text'],
        ];
    }
}
