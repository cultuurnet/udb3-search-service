<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use CultuurNet\UDB3\Search\UnsupportedParameterValue;

enum AttendanceMode: string
{
    case Offline = 'offline';
    case Online = 'online';
    case Mixed = 'mixed';

    public static function fromString(string $value): self
    {
        $attendanceMode = self::tryFrom($value);
        if ($attendanceMode === null) {
            throw new UnsupportedParameterValue('Unknown attendance mode value "' . $value . '"');
        }

        return $attendanceMode;
    }
}
