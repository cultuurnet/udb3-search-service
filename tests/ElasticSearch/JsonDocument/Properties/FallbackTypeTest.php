<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class FallbackTypeTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value, FallbackType $expected): void
    {
        $this->assertSame($expected, FallbackType::from($value));
    }

    public function validValues(): array
    {
        return [
            'Event' => ['Event', FallbackType::Event],
            'Place' => ['Place', FallbackType::Place],
            'Organizer' => ['Organizer', FallbackType::Organizer],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_rejects_invalid_values(string $invalidValue): void
    {
        $this->assertNull(FallbackType::tryFrom($invalidValue));
    }

    public function inValidValues(): array
    {
        return [
            'random' => ['random'],
            'lowercase' => ['event'],
        ];
    }
}
