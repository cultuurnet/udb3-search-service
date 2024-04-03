<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Geocoding\Coordinate;

abstract class Coordinate
{
    private float $value;

    public function __construct(float $value)
    {
        $this->value = $value;
    }

    public function toDouble(): float
    {
        return $this->value;
    }

    public function sameAs(Coordinate $coordinate): bool
    {
        return $this->value === $coordinate->value;
    }
}
