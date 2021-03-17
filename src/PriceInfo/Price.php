<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\PriceInfo;

use CultuurNet\UDB3\Search\Natural;

final class Price extends Natural
{
    public static function fromFloat(float $value): Price
    {
        $precision = 0;
        return new self((int) round($value * 100, $precision));
    }

    public function toFloat(): float
    {
        return $this->toNative() / 100;
    }
}
