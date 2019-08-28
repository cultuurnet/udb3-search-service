<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;
use CultuurNet\UDB3\Search\Offer\FacetName;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use ValueObjects\StringLiteral\StringLiteral;

class LabelsAggregationTransformer implements AggregationTransformerInterface
{
    /**
     * @var FacetName
     */
    private $facetName;

    public function __construct(
        FacetName $facetName
    ) {
        $this->facetName = $facetName;
    }

    /**
     * @param Aggregation $aggregation
     * @return bool
     */
    public function supports(Aggregation $aggregation)
    {
        return $aggregation->getName()->sameValueAs($this->facetName);
    }

    /**
     * @param Aggregation $aggregation
     * @return FacetTreeInterface
     */
    public function toFacetTree(Aggregation $aggregation)
    {
        if (!$this->supports($aggregation)) {
            $name = $aggregation->getName()->toNative();
            throw new \LogicException("Aggregation $name not supported for transformation.");
        }

        $nodes = [];
        foreach ($aggregation->getBuckets() as $bucket) {
            if ($bucket->getCount() == 0) {
                continue;
            }

            // For labels we use the bucket key for all 4 supported
            // languages, because labels are currently not multilingual.
            $translatedName = new StringLiteral($bucket->getKey());

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

        return new FacetFilter($this->facetName->toNative(), $nodes);
    }
}
