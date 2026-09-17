<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

enum SortOrder: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public static function fromString(string $value): self
    {
        $sortOrder = self::tryFrom($value);
        if ($sortOrder === null) {
            throw new UnsupportedParameterValue("Invalid sort order '{$value}' given.");
        }

        return $sortOrder;
    }
}
