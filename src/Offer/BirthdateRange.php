<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Offer;

use DateTimeImmutable;

final class BirthdateRange
{
    private DateTimeImmutable $from;

    private DateTimeImmutable $to;

    private int $minAge;

    private int $maxAge;

    public function __construct(DateTimeImmutable $from, DateTimeImmutable $to, DateTimeImmutable $now)
    {
        $this->from = $from;
        $this->to = $to;
        $this->maxAge = self::ageInYears($from, $now);
        $this->minAge = self::ageInYears($to, $now);
    }

    public function getFrom(): DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): DateTimeImmutable
    {
        return $this->to;
    }

    public function getMinAge(): int
    {
        return $this->minAge;
    }

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    private static function ageInYears(DateTimeImmutable $birthdate, DateTimeImmutable $now): int
    {
        $diff = $now->diff($birthdate);
        if ($diff->invert === 0) {
            return $diff->y;
        }
        return 0;
    }
}
