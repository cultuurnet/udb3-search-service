<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

abstract class StringLiteral
{
    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
