<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;
use DateTimeImmutable;
use stdClass;

final class CopyJsonAvailability implements CopyJsonInterface
{
    /**
     * @var CopyJsonLoggerInterface
     */
    private $copyLogger;

    public function __construct(CopyJsonLoggerInterface $copyLogger)
    {
        $this->copyLogger = $copyLogger;
    }

    public function copy(stdClass $from, stdClass $to): void
    {
        if (isset($from->availableFrom, $from->workflowStatus) && $from->workflowStatus === 'DRAFT') {
            $this->copyLogger->logWarning('Found availableFrom but workflowStatus is DRAFT.');
        }

        $availableFrom = $this->getAvailableDate($from, 'availableFrom');
        $availableTo = $this->getAvailableDate($from, 'availableTo');

        // @todo Fix this in UDB3 and make availableTo for permanent offer consistently 2100-01-01 or null.
        // @see https://jira.uitdatabank.be/browse/III-2529
        // @replay_availableTo Once III-2529 is fixed and a replay is done these fallbacks can be removed.
        if (!$availableTo) {
            // Due to a bug in UDB3, offers imported from UDB2 don't have an availableTo.
            // Generally the availableTo is the same as the endDate, so try to use that instead.
            $availableTo = $this->getAvailableDate($from, 'endDate');
        }
        if (!$availableTo && isset($from->calendarType) && $from->calendarType === 'permanent') {
            // If the offer has no endDate either, it's probably a "permanent" offer.
            // In that case the availableTo is generally '2100-01-01T00:00:00+00:00' on the JSON-LD.
            // It's just missing for offer imported from UDB2.
            // We could also have a half-open availableRange (without end date), but that would not
            // be consistent with existing permanent offers that do have an availableTo set in 2100.
            // We also need to set it to 2100-01-01 instead of leaving it open so we can sort on it.
            $availableTo = DateTimeImmutable::createFromFormat(\DateTime::ATOM, '2100-01-01T00:00:00+00:00');
        }

        if ($availableFrom > $availableTo) {
            // In some test cases of external developers, an event is created with very old calendar data and then
            // published, resulting in an availableFrom (publication date) that's higher than the availableTo (end date
            // of the event). We cannot index a range that starts with a higher from than to, so we set the availableTo
            // to the same date as the availableFrom so it gets indexed and appears in the developer's dashboard.
            $availableTo = $availableFrom;
        }

        if ($availableTo) {
            $to->availableTo = $availableTo->format(\DateTime::ATOM);
        }

        if (!$availableFrom) {
            return;
        }

        $to->availableRange = new stdClass();
        $to->availableRange->gte = $availableFrom->format(\DateTime::ATOM);

        if ($availableTo) {
            $to->availableRange->lte = $availableTo->format(\DateTime::ATOM);
        }
    }

    private function getAvailableDate(stdClass $from, string $propertyName): ?DateTimeImmutable
    {
        if (!isset($from->{$propertyName})) {
            return null;
        }

        // Convert to DateTimeImmutable to verify the format is correct.
        $date = DateTimeImmutable::createFromFormat(\DateTime::ATOM, $from->{$propertyName});

        if (!$date) {
            $this->copyLogger->logError("Could not parse {$propertyName} as an ISO-8601 datetime.");
            return null;
        }

        return $date;
    }
}
