<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;

final class SortOrder
{
    public const ASC = 'asc';
    public const DESC = 'desc';

    /**
     * @var string
     */
    private $order;

    public function __construct(string $order)
    {
        if (!in_array($order, $this->getAllowedValues())) {
            throw new InvalidArgumentException('The given sort order ' . $order . ' is not asc or desc');
        }

        $this->order = $order;
    }

    public static function asc(): self
    {
        return new self(self::ASC);
    }

    public static function desc(): self
    {
        return new self(self::DESC);
    }

    public function toString(): string
    {
        return $this->order;
    }

    private function getAllowedValues(): array
    {
        return [
            self::ASC,
            self::DESC,
        ];
    }
}
