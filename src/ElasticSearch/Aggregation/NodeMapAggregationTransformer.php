<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Aggregation;

use LogicException;
use InvalidArgumentException;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Language\MultilingualString;
use CultuurNet\UDB3\Search\Offer\FacetName;

final class NodeMapAggregationTransformer implements AggregationTransformerInterface
{
    private FacetName $facetName;

    private array $nodeMap;

    /**
     * @param array $nodeMap
     *   Example structure:
     *   [
     *     'prv-vlaams-brabant' => [
     *       'name' => [
     *         'nl' => 'Vlaams-Brabant',
     *       ],
     *       'children' => [
     *         'gem-leuven' => [
     *           'name' => [
     *             'nl' => 'Leuven',
     *           ],
     *         ],
     *       ],
     *     ],
     *   ];
     */
    public function __construct(
        FacetName $facetName,
        array $nodeMap
    ) {
        $this->validateNodeMap($nodeMap);

        $this->facetName = $facetName;
        $this->nodeMap = $nodeMap;
    }


    public function supports(Aggregation $aggregation): bool
    {
        return $aggregation->getName()->sameValueAs($this->facetName);
    }

    public function toFacetTree(Aggregation $aggregation): FacetFilter
    {
        if (!$this->supports($aggregation)) {
            $name = $aggregation->getName()->toString();
            throw new LogicException("Aggregation $name not supported for transformation.");
        }

        $children = $this->transformNodeMapToFacetNodes($this->nodeMap, $aggregation->getBuckets());
        return new FacetFilter($this->facetName->toString(), $children);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateNodeMap(array $nodeMap): void
    {
        foreach ($nodeMap as $key => $node) {
            if (!is_string($key)) {
                throw new InvalidArgumentException("Facet node $key has an invalid key.");
            }

            if (!isset($node['name']) || empty($node['name'])) {
                throw new InvalidArgumentException("Facet node $key has no name.");
            }

            if (!is_array($node['name'])) {
                throw new InvalidArgumentException("Facet node $key has a string as name, but it should be an array.");
            }

            foreach ($node['name'] as $language => $value) {
                // Should throw an exception if the language is invalid.
                new Language($language);
            }

            if (isset($node['children']) && !is_array($node['children'])) {
                throw new InvalidArgumentException("Children of facet node $key should be an associative array.");
            }

            if (isset($node['children'])) {
                $this->validateNodeMap($node['children']);
            }
        }
    }

    /**
     * @param Bucket[] $buckets
     * @return FacetNode[]
     */
    private function transformNodeMapToFacetNodes(array $nodeMap, array $buckets): array
    {
        $nodes = [];

        foreach ($nodeMap as $key => $nodeData) {
            if (!isset($buckets[$key])) {
                continue;
            }

            $count = $buckets[$key]->getCount();

            if ($count == 0) {
                continue;
            }

            foreach ($nodeData['name'] as $language => $value) {
                /* @var MultilingualString $name */
                if (!isset($name)) {
                    $name = new MultilingualString(
                        new Language($language),
                        $value
                    );
                } else {
                    $name = $name->withTranslation(
                        new Language($language),
                        $value
                    );
                }
            }

            if (!isset($name)) {
                continue;
            }

            $children = [];
            if (isset($nodeData['children'])) {
                $children = $this->transformNodeMapToFacetNodes($nodeData['children'], $buckets);
            }

            $nodes[] = new FacetNode($key, $name, $count, $children);
            unset($name);
        }

        return $nodes;
    }
}
