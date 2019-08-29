<?php

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Facet\FacetFilter;
use ValueObjects\Number\Natural;

class PagedResultSet
{
    /**
     * @var Natural
     */
    private $total;

    /**
     * @var Natural
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
     * @param Natural $total
     * @param Natural $perPage
     * @param JsonDocument[] $results
     */
    public function __construct(
        Natural $total,
        Natural $perPage,
        array $results
    ) {
        $this->guardResults($results);

        $this->total = $total;
        $this->perPage = $perPage;
        $this->results = $results;
        $this->facets = [];
    }

    /**
     * @return Natural
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @return Natural
     */
    public function getPerPage()
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

    /**
     * @param array $results
     */
    private function guardResults(array $results)
    {
        foreach ($results as $result) {
            if (!($result instanceof JsonDocument)) {
                throw new \InvalidArgumentException('Results should be an array of JsonDocument objects.');
            }
        }
    }
}
