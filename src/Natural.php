<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

abstract class Natural
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0) {
            $classNameParts = array_reverse(explode('\\', get_class($this)));
            $className = reset($classNameParts);
            throw new UnsupportedParameterValue($className .' should be 0 or bigger');
        }

        $this->value = $value;
    }

    public function toNative(): int
    {
        return $this->value;
    }

    public function toString(): string
    {
        return (string) $this->value;
    }
}
