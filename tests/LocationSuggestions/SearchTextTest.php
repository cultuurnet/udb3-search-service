<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\LocationSuggestions;

use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\TestCase;

final class SearchTextTest extends TestCase
{
    /**
     * @test
     * @dataProvider provideSearchTexts
     */
    public function it_knows_where_the_numbers_are(string $text, bool $containsNumber, bool $endsWithNumber): void
    {
        $searchText = new SearchText($text);

        $this->assertSame($containsNumber, $searchText->containsNumber());
        $this->assertSame($endsWithNumber, $searchText->endsWithNumber());
    }

    public function provideSearchTexts(): array
    {
        return [
            'street' => ['lentedreef', false, false],
            'house number at the end' => ['lentedreef 2', true, true],
            'house number with a letter' => ['kerkstraat 12a', true, true],
            'city after the house number' => ['kerkstraat 12 gent', true, false],
            'postal code first' => ['3010 lentedreef', true, false],
            'trailing spaces' => ['lentedreef 2  ', true, true],
        ];
    }

    /**
     * @test
     * @dataProvider provideOnlyNumbers
     */
    public function it_knows_when_only_numbers_are_typed(string $text, bool $isOnlyNumbers): void
    {
        $this->assertSame($isOnlyNumbers, (new SearchText($text))->isOnlyNumbers());
    }

    public function provideOnlyNumbers(): array
    {
        return [
            'postal code' => ['3010', true],
            'postal code being typed' => ['30', true],
            'two numbers' => ['3010 12', true],
            'house number with a letter' => ['12a', false],
            'street and house number' => ['lentedreef 2', false],
            'street' => ['lentedreef', false],
        ];
    }

    /**
     * @test
     */
    public function it_requires_text(): void
    {
        $this->expectException(MissingParameter::class);

        new SearchText('  ');
    }

    /**
     * @test
     */
    public function it_refuses_text_longer_than_120_characters(): void
    {
        $this->expectException(UnsupportedParameterValue::class);

        new SearchText(str_repeat('a', 121));
    }
}
