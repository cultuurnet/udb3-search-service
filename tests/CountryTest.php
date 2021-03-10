<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CountryTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value): void
    {
        new Country($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            'AA' => ['AA'],
            'BE' => ['BE'],
            'FR' => ['FR'],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Country($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'be' => ['be'],
            'BEL' => ['BEL'],
            'B' => ['B'],
        ];
    }
}
