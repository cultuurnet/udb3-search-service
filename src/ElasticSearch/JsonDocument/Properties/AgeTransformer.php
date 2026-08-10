<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosDate;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

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
            return self::toPlainDate($startDate);
        }

        if (($from['calendarType'] ?? null) === 'permanent') {
            return self::toPlainDate($this->now);
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

        $oldestBirthdate = self::toPlainDate($gte);
        $youngestBirthdate = self::toPlainDate($lte);

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

        $reference = new ChronosDate($referenceDate);

        // No maximum age means no oldest birthdate, indexed as null so the range stays open.
        $oldestBirthdate = null;
        if ($maxAge !== null) {
            // Someone born exactly maxAge + 1 years ago already had that birthday, so the oldest
            // birthdate is a day later.
            $oldestBirthdate = $reference->subYears($maxAge + 1)->addDays(1)->format('Y-m-d');
        }

        $youngestBirthdate = $reference->subYears($minAge)->format('Y-m-d');

        $draft['birthdateRange'] = [
            'gte' => $oldestBirthdate,
            'lte' => $youngestBirthdate,
        ];

        return $draft;
    }

    /**
     * A ChronosDate holds no time and no offset, so a start date at 00:30+01:00 counts as that day
     * instead of the day before in UTC. It throws on a date it cannot read, which is caught here so a
     * single malformed date only skips the conversion instead of failing the whole document.
     */
    private static function toPlainDate(string|DateTimeInterface $date): ?DateTimeImmutable
    {
        try {
            return (new ChronosDate($date))->toNative();
        } catch (Throwable) {
            return null;
        }
    }
}
