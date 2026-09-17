<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\TestCase;

final class StatusTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value, Status $expected): void
    {
        $this->assertSame($expected, Status::fromString($value));
    }

    public function validValues(): array
    {
        return [
            'Available' => ['Available', Status::Available],
            'Unavailable' => ['Unavailable', Status::Unavailable],
            'TemporarilyUnavailable' => ['TemporarilyUnavailable', Status::TemporarilyUnavailable],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('Unknown status value "' . $invalidValue . '"');

        Status::fromString($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'random' => ['random'],
            'lowercase' => ['available'],
        ];
    }
}
