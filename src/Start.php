<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;

final class Start
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 10000) {
            throw new InvalidArgumentException('The "start" parameter should be between 0 and 10000');
        }

        $this->value = $value;
    }

    public function toInteger(): int
    {
        return $this->value;
    }
}
