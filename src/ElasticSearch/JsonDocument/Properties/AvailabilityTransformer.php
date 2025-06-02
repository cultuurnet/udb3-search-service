<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\DateTimeFactory;
use DateTime;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;
use DateTimeImmutable;

final class AvailabilityTransformer implements JsonTransformer
{
    private JsonTransformerLogger $logger;

    public function __construct(JsonTransformerLogger $logger)
    {
        $this->logger = $logger;
    }

    public function transform(array $from, array $draft = []): array
    {
        if (isset($from['availableFrom'], $from['workflowStatus']) && $from['workflowStatus'] === 'DRAFT') {
            $this->logger->logWarning('Found availableFrom but workflowStatus is DRAFT.');
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
        if (!$availableTo && isset($from['calendarType']) && $from['calendarType'] === 'permanent') {
            // If the offer has no endDate either, it's probably a "permanent" offer.
            // In that case the availableTo is generally '2100-01-01T00:00:00+00:00' on the JSON-LD.
            // It's just missing for offer imported from UDB2.
            // We could also have a half-open availableRange (without end date), but that would not
            // be consistent with existing permanent offers that do have an availableTo set in 2100.
            // We also need to set it to 2100-01-01 instead of leaving it open so we can sort on it.
            $availableTo = DateTimeFactory::fromAtom('2100-01-01T00:00:00+00:00');
        }

        if ($availableFrom > $availableTo) {
            // In some test cases of external developers, an event is created with very old calendar data and then
            // published, resulting in an availableFrom (publication date) that's higher than the availableTo (end date
            // of the event). We cannot index a range that starts with a higher from than to, so we set the availableTo
            // to the same date as the availableFrom so it gets indexed and appears in the developer's dashboard.
            $availableTo = $availableFrom;
        }

        if ($availableTo) {
            $draft['availableTo'] = $availableTo->format(DateTime::ATOM);
        }

        if (!$availableFrom) {
            return $draft;
        }

        $draft['availableRange']['gte'] = $availableFrom->format(DateTime::ATOM);

        if ($availableTo) {
            $draft['availableRange']['lte'] = $availableTo->format(DateTime::ATOM);
        }

        return $draft;
    }

    private function getAvailableDate(array $from, string $propertyName): ?DateTimeImmutable
    {
        if (!isset($from[$propertyName])) {
            return null;
        }

        // Convert to DateTimeImmutable to verify the format is correct.
        $date = DateTimeImmutable::createFromFormat(DateTime::ATOM, $from[$propertyName]);

        if (!$date) {
            $this->logger->logError("Could not parse {$propertyName} as an ISO-8601 datetime.");
            return null;
        }

        return $date;
    }
}
