<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\Offer\FacetName;

final class LabelsAggregationTransformer implements AggregationTransformerInterface
{
    private FacetName $facetName;

    public function __construct(
        FacetName $facetName
    ) {
        $this->facetName = $facetName;
    }

    public function supports(Aggregation $aggregation): bool
    {
        return $aggregation->getName()->sameValueAs($this->facetName);
    }

    public function toFacetTree(Aggregation $aggregation): FacetTreeInterface
    {
        if (!$this->supports($aggregation)) {
            $name = $aggregation->getName()->toString();
            throw new LogicException("Aggregation $name not supported for transformation.");
        }

        $nodes = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            if ($bucket->getCount() === 0) {
                continue;
            }

            // For labels we use the bucket key for all 4 supported
            // languages, because labels are currently not multilingual.
            $translatedName = $bucket->getKey();

            $name = new MultilingualString(
                new Language('nl'),
                $translatedName
            );

            foreach (['fr', 'de', 'en'] as $langCode) {
                $name = $name->withTranslation(
                    new Language($langCode),
                    $translatedName
                );
            }

            $nodes[] = new FacetNode(
                $bucket->getKey(),
                $name,
                $bucket->getCount()
            );
        }

        return new FacetFilter($this->facetName->toString(), $nodes);
    }
}
