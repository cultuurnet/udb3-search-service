<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use PHPUnit\Framework\TestCase;

final class SortOrderTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value, SortOrder $expected): void
    {
        $this->assertSame($expected, SortOrder::fromString($value));
    }

    public function validValues(): array
    {
        return [
            'asc' => ['asc', SortOrder::Asc],
            'desc' => ['desc', SortOrder::Desc],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage("Invalid sort order '{$invalidValue}' given.");

        SortOrder::fromString($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'random' => ['random'],
            'ASC' => ['ASC'],
            'DESC' => ['DESC'],
        ];
    }
}
