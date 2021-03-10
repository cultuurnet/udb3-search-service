<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TimeTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(int $value): void
    {
        new Time($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            '0' => [0],
            '2359' => [2359],
            '1205' => [1205],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(int $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Time($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            '-1' => [-1],
            '2360' => [2360],
        ];
    }
}
