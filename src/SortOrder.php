<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use InvalidArgumentException;

final class SortOrder
{
    private const ASC = 'asc';
    private const DESC = 'desc';

    private static $allowedValues = [
        self::ASC,
        self::DESC,
    ];

    /**
     * @var string
     */
    private $order;

    public function __construct(string $order)
    {
        if (!in_array($order, self::$allowedValues)) {
            throw new InvalidArgumentException(
                'Invalid SortOrder: ' . $order . '. Should be one of ' . implode(', ', self::$allowedValues)
            );
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
}
