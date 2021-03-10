<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\PriceInfo;

use CultuurNet\UDB3\Search\Natural;
use InvalidArgumentException;

final class Price extends Natural
{
    public static function fromFloat(float $value): Price
    {
        if (!is_float($value)) {
            throw new InvalidArgumentException($value, ['float']);
        }

        $precision = 0;
        return new Price((int) round($value * 100, $precision));
    }

    public function toFloat(): float
    {
        return $this->toNative() / 100;
    }
}
