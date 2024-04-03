<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

use InvalidArgumentException;

abstract class AbstractFacetTree implements FacetTreeInterface
{
    private string $key;

    /**
     * @var FacetNode[]
     */
    private array $children = [];

    /**
     * @param string $key
     */
    public function __construct(
        $key,
        array $children = []
    ) {
        $this->setKey($key);
        $this->setChildren(...$children);
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return FacetNode[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param string $key
     */
    private function setKey($key): void
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Facet tree key should be a string.');
        }
        $this->key = $key;
    }

    /**
     * @param FacetNode[] ...$facetMembers
     */
    private function setChildren(FacetNode ...$facetMembers): void
    {
        $this->children = $facetMembers;
    }
}
