<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class FallbackTypeTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value): void
    {
        new FallbackType($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            'Event' => ['Event'],
            'Place' => ['Place'],
            'Organizer' => ['Organizer'],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FallbackType($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'Unknown' => ['Unknown'],
            'event' => ['event'],
            'EVENT' => ['EVENT'],
        ];
    }
}
