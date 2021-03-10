<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SortOrderTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value): void
    {
        new SortOrder($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            'asc' => ['asc'],
            'desc' => ['desc'],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SortOrder($invalidValue);
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
