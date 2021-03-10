<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfferTypeTest extends TestCase
{
    /**
     * @test
     * @dataProvider validValues
     */
    public function it_only_accepts_valid_values(string $value): void
    {
        new OfferType($value);
        $this->addToAssertionCount(1);
    }

    public function validValues(): array
    {
        return [
            'Event' => ['Event'],
            'Place' => ['Place'],
        ];
    }

    /**
     * @test
     * @dataProvider inValidValues
     */
    public function it_throws_on_invalid_values(string $invalidValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OfferType($invalidValue);
    }

    public function inValidValues(): array
    {
        return [
            'unknown' => ['unknown'],
            'event' => ['event'],
            'EVENT' => ['EVENT'],
        ];
    }
}
