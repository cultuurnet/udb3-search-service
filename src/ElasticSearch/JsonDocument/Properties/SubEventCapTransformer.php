<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class SubEventCapTransformer implements JsonTransformer
{
    public const DEFAULT_CAP = 9900;

    private JsonTransformerLogger $logger;

    private int $subEventCap;

    public function __construct(JsonTransformerLogger $logger, int $subEventCap = self::DEFAULT_CAP)
    {
        $this->logger = $logger;
        $this->subEventCap = $subEventCap;
    }

    public function transform(array $from, array $draft = []): array
    {
        if (!isset($draft['subEvent']) || !is_array($draft['subEvent'])) {
            return $draft;
        }

        if (count($draft['subEvent']) <= $this->subEventCap) {
            return $draft;
        }

        $id = $from['@id'] ?? 'unknown';
        $draft['subEvent'] = $this->truncate($draft['subEvent'], $id);

        return $draft;
    }

    /**
     * Keeps the first $this->subEventCap entries and drops the rest.
     *
     * This is a naive, non-negotiated cut-off: no attempt is made to pick which entries matter most (e.g.
     * biasing towards future-relevant slots, or sampling across the full range). For a periodic calendar
     * with a very long startDate-endDate range and dense opening hours, this can drop several years from
     * the tail end of the range. That's accepted: the scenario is rare, and losing a few years off an
     * already multi-decade range is a small relative loss compared to the complexity of a smarter
     * selection strategy.
     *
     * @param array[] $subEvents
     */
    private function truncate(array $subEvents, string $id): array
    {
        $originalCount = count($subEvents);

        $this->logger->logWarning(
            "subEvent truncated from {$originalCount} to {$this->subEventCap} entries for {$id}."
        );

        return array_slice($subEvents, 0, $this->subEventCap);
    }
}
