<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CalendarSummaryEmbeddingJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdEmbeddingJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\JsonLdPolyfillJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\MinimalRequiredInfoJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\RegionEmbeddingJsonTransformer;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\UiTPASPricesTransformer;
use CultuurNet\UDB3\Search\JsonDocument\CompositeJsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;

final class ResultTransformerFactory
{
    public static function create(
        bool $embedded,
        bool $embedUiTPASPrices,
        CalendarSummaryFormat ...$calendarSummaryFormats
    ): JsonTransformer {
        $transformerStack = new CompositeJsonTransformer();

        if ($embedded) {
            $transformerStack = $transformerStack->addTransformer(new JsonLdEmbeddingJsonTransformer());
            $transformerStack = $transformerStack->addTransformer(new JsonLdPolyfillJsonTransformer());
            $transformerStack = $transformerStack->addTransformer(new RegionEmbeddingJsonTransformer());
            if (!$embedUiTPASPrices) {
                $transformerStack = $transformerStack->addTransformer(new UiTPASPricesTransformer());
            }
        } else {
            $transformerStack = $transformerStack->addTransformer(new MinimalRequiredInfoJsonTransformer());
        }

        if (!empty($calendarSummaryFormats)) {
            $transformerStack = $transformerStack->addTransformer(
                new CalendarSummaryEmbeddingJsonTransformer(...$calendarSummaryFormats)
            );
        }

        return $transformerStack;
    }
}
