<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FacetNameTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value): void
    {
        new FacetName($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            'regions' => ['regions'],
            'types' => ['types'],
            'themes' => ['themes'],
            'facilities' => ['facilities'],
            'labels' => ['labels'],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid FacetName: ' . $invalidValue . '. Should be one of regions, types, themes, facilities, labels'
        );
        new FacetName($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'unknown' => ['unknown'],
            'Regions' => ['Regions'],
            'REGIONS' => ['REGIONS'],
        ];
    }
}
