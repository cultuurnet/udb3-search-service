<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateInterval;
use DateTime;
use DateTimeImmutable;

/**
 * Indexes an equivalent typicalAgeRange for events that only have a birthdateRange, and an
 * equivalent birthdateRange for events that only have a typicalAgeRange.
 *
 * @see docs/calendar-indexing.md
 */
final class AgeTransformer implements JsonTransformer
{
    private DateTimeImmutable $now;

    public function __construct(?DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new Chronos();
    }

    public function transform(array $from, array $draft = []): array
    {
        $hasAgeRange = isset($draft['typicalAgeRange']);
        $hasBirthdateRange = isset($draft['birthdateRange']);

        if ($hasAgeRange === $hasBirthdateRange) {
            return $draft;
        }

        $referenceDate = $this->determineReferenceDate($from);
        if ($referenceDate === null) {
            return $draft;
        }

        return $hasBirthdateRange
            ? $this->addTypicalAgeRange($draft, $referenceDate)
            : $this->addBirthdateRange($draft, $referenceDate);
    }

    /**
     * Permanent events have no startDate to convert against, so they use the indexing time and go
     * stale until udb3-core:reindex-permanent runs again. Any other event without a startDate is
     * missing data that CalendarTransformer already logs, and is skipped rather than converted
     * against an arbitrary date.
     */
    private function determineReferenceDate(array $from): ?DateTimeImmutable
    {
        $startDate = $from['startDate'] ?? null;

        if (is_string($startDate)) {
            $parsed = DateTimeImmutable::createFromFormat(DateTime::ATOM, $startDate);

            return $parsed === false ? null : self::fromDateString($parsed->format('Y-m-d'));
        }

        if (($from['calendarType'] ?? null) === 'permanent') {
            return self::fromDateString($this->now->format('Y-m-d'));
        }

        return null;
    }

    private function addTypicalAgeRange(array $draft, DateTimeImmutable $referenceDate): array
    {
        $gte = $draft['birthdateRange']['gte'] ?? null;
        $lte = $draft['birthdateRange']['lte'] ?? null;

        if (!is_string($gte) || !is_string($lte)) {
            return $draft;
        }

        $oldestBirthdate = self::fromDateString($gte);
        $youngestBirthdate = self::fromDateString($lte);

        if ($oldestBirthdate === null || $youngestBirthdate === null) {
            return $draft;
        }

        try {
            // Shared with the birthdateRangeFrom/birthdateRangeTo parameters, so index and query
            // agree on how old someone is on a given date.
            $range = new BirthdateRange($oldestBirthdate, $youngestBirthdate, $referenceDate);
        } catch (UnsupportedParameterValue $e) {
            return $draft;
        }

        $draft['typicalAgeRange'] = [
            'gte' => $range->getMinAge(),
            'lte' => $range->getMaxAge(),
        ];
        $draft['allAges'] = false;

        return $draft;
    }

    private function addBirthdateRange(array $draft, DateTimeImmutable $referenceDate): array
    {
        // An "all ages" range covers every birthdate, so converting it would match every query.
        if (($draft['allAges'] ?? false) === true) {
            return $draft;
        }

        $minAge = $draft['typicalAgeRange']['gte'] ?? null;
        $maxAge = $draft['typicalAgeRange']['lte'] ?? null;

        if (!is_int($minAge) || ($maxAge !== null && !is_int($maxAge))) {
            return $draft;
        }

        $birthdateRange = [];

        // Someone born exactly maxAge + 1 years ago already had that birthday, so the oldest
        // birthdate is a day later. Without a maximum age there is no oldest birthdate at all.
        if ($maxAge !== null) {
            $birthdateRange['gte'] = self::subtractYears($referenceDate, $maxAge + 1)
                ->add(new DateInterval('P1D'))
                ->format('Y-m-d');
        }

        $birthdateRange['lte'] = self::subtractYears($referenceDate, $minAge)->format('Y-m-d');

        $draft['birthdateRange'] = $birthdateRange;

        return $draft;
    }

    /**
     * Subtracting years from 29 February lands on 1 March, which shifts both ends of the derived
     * range a day away from the age BirthdateRange would calculate. Only February can overflow, so
     * stepping back a day is enough to land on its last day.
     */
    private static function subtractYears(DateTimeImmutable $date, int $years): DateTimeImmutable
    {
        $result = $date->sub(new DateInterval('P' . $years . 'Y'));

        return $result->format('d') === $date->format('d')
            ? $result
            : $result->sub(new DateInterval('P1D'));
    }

    private static function fromDateString(string $date): ?DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed === false ? null : $parsed;
    }
}
