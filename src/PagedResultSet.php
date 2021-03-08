<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Facet\FacetFilter;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;

final class PagedResultSet
{
    /**
     * @var int
     */
    private $total;

    /**
     * @var int
     */
    private $perPage;

    /**
     * @var array
     */
    private $results;

    /**
     * @var FacetFilter[]
     */
    private $facets;

    /**
     * @param JsonDocument[] $results
     */
    public function __construct(
        int $total,
        int $perPage,
        array $results
    ) {
        $this->guardResults($results);

        $this->total = $total;
        $this->perPage = $perPage;
        $this->results = $results;
        $this->facets = [];
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param FacetFilter[] ...$facetFilters
     * @return PagedResultSet
     */
    public function withFacets(FacetFilter ...$facetFilters)
    {
        $c = clone $this;
        $c->facets = $facetFilters;
        return $c;
    }

    /**
     * @return FacetFilter[]
     */
    public function getFacets()
    {
        return $this->facets;
    }


    private function guardResults(array $results)
    {
        foreach ($results as $result) {
            if (!($result instanceof JsonDocument)) {
                throw new \InvalidArgumentException('Results should be an array of JsonDocument objects.');
            }
        }
    }
}
