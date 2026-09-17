<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\TestCase;

final class FacetNameTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value, FacetName $expected): void
    {
        $this->assertSame($expected, FacetName::fromString($value));
    }

    public function validValues(): array
    {
        return [
            'regions' => ['regions', FacetName::Regions],
            'types' => ['types', FacetName::Types],
            'themes' => ['themes', FacetName::Themes],
            'facilities' => ['facilities', FacetName::Facilities],
            'labels' => ['labels', FacetName::Labels],
            'uppercase is accepted' => ['REGIONS', FacetName::Regions],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage("Unknown facet name '{$invalidValue}'.");

        FacetName::fromString($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'random' => ['random'],
            'empty' => [''],
        ];
    }
}
