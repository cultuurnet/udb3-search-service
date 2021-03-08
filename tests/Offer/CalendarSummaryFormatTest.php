<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

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
            ['xs-html', 'html', 'xs'],
            ['sm-html', 'html', 'sm'],
            ['md-html', 'html', 'md'],
            ['lg-html', 'html', 'lg'],
        ];
    }
}
