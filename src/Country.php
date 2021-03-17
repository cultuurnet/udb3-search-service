<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class Country
{
    /**
     * @var string
     */
    private $country;

    public function __construct(string $code)
    {
        if (!preg_match('/^[A-Z]{2}$/', $code)) {
            throw new UnsupportedParameterValue('Country code ' . $code . ' is not supported');
        }

        $this->country = $code;
    }

    public function toString(): string
    {
        return $this->country;
    }
}
