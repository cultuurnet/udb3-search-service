<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

abstract class AbstractFacetTree implements FacetTreeInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var FacetNode[]
     */
    private $children = [];

    /**
     * @param string $key
     * @param array $children
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
    private function setKey($key)
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException('Facet tree key should be a string.');
        }
        $this->key = $key;
    }

    /**
     * @param FacetNode[] ...$facetMembers
     */
    private function setChildren(FacetNode ...$facetMembers)
    {
        $this->children = $facetMembers;
    }
}
