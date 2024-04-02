<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Facet\FacetNode;
use CultuurNet\UDB3\Search\Facet\FacetTreeInterface;

final class NodeAwareFacetTreeNormalizer implements FacetTreeNormalizerInterface
{
    public function normalize(FacetTreeInterface $facetTree): array
    {
        $data = [];

        if ($facetTree instanceof FacetNode) {
            $data['name'] = $facetTree->getName()->getTranslationsIncludingOriginal();
            $data['count'] = $facetTree->getCount();

            $data['children'] = [];
            $normalizedChildren = &$data['children'];
        } else {
            $normalizedChildren = &$data;
        }

        foreach ($facetTree->getChildren() as $child) {
            $normalizedChildren[$child->getKey()] = $this->normalize($child);
        }

        if (isset($data['children']) && empty($data['children'])) {
            unset($data['children']);
        }

        return $data;
    }
}
