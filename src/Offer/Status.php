<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

enum Status: string
{
    case Available = 'Available';
    case Unavailable = 'Unavailable';
    case TemporarilyUnavailable = 'TemporarilyUnavailable';

    public static function fromString(string $value): self
    {
        $status = self::tryFrom($value);
        if ($status === null) {
            throw new UnsupportedParameterValue('Unknown status value "' . $value . '"');
        }

        return $status;
    }
}
