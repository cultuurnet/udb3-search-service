<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

final class FacetTreeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_a_multi_level_list_of_facet_node_children()
    {
        $gemLeuven = new FacetNode(
            'gem-leuven',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Leuven')
            ),
            3
        );

        $gemWilsele = new FacetNode(
            'gem-wilsele',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Wilsele')
            ),
            14
        );

        $gemWijgmaal = new FacetNode(
            'facet13',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Wijgmaal')
            ),
            15
        );

        $prvVlaamsBrabant = new FacetNode(
            'prv-vlaams-brabant',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Vlaams-Brabant')
            ),
            32,
            [$gemLeuven, $gemWilsele, $gemWijgmaal]
        );

        $gemBerchem= new FacetNode(
            'gem-berchem',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Berchem')
            ),
            7
        );

        $gemWesterlo = new FacetNode(
            'gem-westerlo',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Westerlo')
            ),
            8
        );

        $gemAntwerpen = new FacetNode(
            'gem-antwerpen',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Antwerpen')
            ),
            13
        );

        $prvAntwerpen = new FacetNode(
            'prv-antwerpen',
            new MultilingualString(
                new Language('nl'),
                new StringLiteral('Antwerpen')
            ),
            28,
            [$gemBerchem, $gemWesterlo, $gemAntwerpen]
        );

        $filter = new FacetFilter('region', [$prvVlaamsBrabant, $prvAntwerpen]);

        // Don't use assertEquals because we want to test that we can get all
        // required info by using the getters on the facet filter and nodes.
        $this->assertFilterEquals('region', [$prvVlaamsBrabant, $prvAntwerpen], $filter);
    }

    /**
     * @test
     */
    public function it_only_accepts_a_string_as_key()
    {
        $this->expectException(\InvalidArgumentException::class);
        new FacetFilter(123, []);
    }

    /**
     * @test
     */
    public function it_only_accepts_an_int_as_count()
    {
        $this->expectException(\InvalidArgumentException::class);
        new FacetNode('test', new MultilingualString(new Language('nl'), new StringLiteral('test')), 'count', []);
    }

    private function assertFilterEquals(string $expectedKey, array $expectedChildren, FacetFilter $actual): void
    {
        $this->assertEquals($expectedKey, $actual->getKey());
        $this->assertChildrenEquals($expectedChildren, $actual->getChildren());
    }


    private function assertChildrenEquals(array $expected, array $actual)
    {
        $this->assertEquals(count($expected), count($actual));

        for ($i = 0; $i < count($expected); $i++) {
            $this->assertNodeEquals($expected[$i], $actual[$i]);
        }
    }


    private function assertNodeEquals(FacetNode $expected, FacetNode $actual)
    {
        $this->assertEquals($expected->getKey(), $actual->getKey());
        $this->assertEquals($expected->getName(), $actual->getName());
        $this->assertEquals($expected->getCount(), $actual->getCount());
        $this->assertChildrenEquals($expected->getChildren(), $actual->getChildren());
    }
}
