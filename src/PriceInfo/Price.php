<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\PriceInfo;

use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\Number\Natural;

final class Price extends Natural
{
    public static function fromFloat(float $value): Price
    {
        if (!is_float($value)) {
            throw new InvalidNativeArgumentException($value, ['float']);
        }

        $precision = 0;
        return new Price((int) round($value * 100, $precision));
    }

    public function toFloat(): float
    {
        return $this->toNative() / 100;
    }
}
