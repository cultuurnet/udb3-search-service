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
 * Derives the missing one of typicalAgeRange / birthdateRange at index time. See the table under
 * "Ages and birthdates" in docs/calendar-indexing.md for what each event ends up with.
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
        $referenceDate = $this->determineReferenceDate($from);
        if ($referenceDate === null) {
            return $draft;
        }

        // A birthdate range overrides the default all-ages age ("-"); a real entered age is kept.
        if (isset($draft['birthdateRange'])) {
            $ageIsDefault = ($from['typicalAgeRange'] ?? '-') === '-';
            return $ageIsDefault ? $this->addTypicalAgeRange($draft, $referenceDate) : $draft;
        }

        return $this->addBirthdateRange($draft, $referenceDate);
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
        // An "all ages" event suits every birthdate. Index an unbounded range so it matches every
        // birthdate query, the same way its typicalAgeRange already matches every age query. An
        // integrator that does not want these events excludes them with allAges=false.
        if (($draft['allAges'] ?? false) === true) {
            $draft['birthdateRange'] = ['gte' => null, 'lte' => null];
            return $draft;
        }

        $minAge = $draft['typicalAgeRange']['gte'] ?? null;
        $maxAge = $draft['typicalAgeRange']['lte'] ?? null;

        if (!is_int($minAge) || ($maxAge !== null && !is_int($maxAge))) {
            return $draft;
        }

        // No maximum age means no oldest birthdate, indexed as null so the range stays open.
        $oldestBirthdate = null;
        if ($maxAge !== null) {
            // Someone born exactly maxAge + 1 years ago already had that birthday, so the oldest
            // birthdate is a day later.
            $oldestBirthdate = self::subtractYears($referenceDate, $maxAge + 1)
                ->add(new DateInterval('P1D'))
                ->format('Y-m-d');
        }

        $youngestBirthdate = self::subtractYears($referenceDate, $minAge)->format('Y-m-d');

        $draft['birthdateRange'] = [
            'gte' => $oldestBirthdate,
            'lte' => $youngestBirthdate,
        ];

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
