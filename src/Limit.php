<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;

final class Limit
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 2000) {
            throw new InvalidArgumentException('The "limit" parameter should be between 0 and 2000');
        }

        if ($value === 0) {
            $value = 30;
        }

        $this->value = $value;
    }

    public function toInteger(): int
    {
        return $this->value;
    }
}
