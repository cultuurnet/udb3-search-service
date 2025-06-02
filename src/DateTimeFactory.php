<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class DateTimeFactory
{
    public static function fromAtom(string $datetime): DateTimeImmutable
    {
        $format = DateTimeInterface::ATOM;
        $object = DateTimeImmutable::createFromFormat($format, $datetime);

        if ($object instanceof DateTimeImmutable) {
            return $object;
        }

        throw new InvalidArgumentException($datetime . ' does not appear to be a valid ' . $format . ' datetime string.');
    }
}
