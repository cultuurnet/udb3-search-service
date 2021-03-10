<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StatusTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value): void
    {
        new Status($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            'Available' => ['Available'],
            'Unavailable' => ['Unavailable'],
            'TemporarilyUnavailable' => ['TemporarilyUnavailable'],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Status($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'unknown' => ['unknown'],
            'available' => ['Available'],
            'AVAILABLE' => ['AVAILABLE'],
        ];
    }
}
