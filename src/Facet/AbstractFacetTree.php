<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

abstract class AbstractFacetTree implements FacetTreeInterface
{
    private string $key;

    /**
     * @var FacetNodeInterface[]
     */
    private array $children = [];

    public function __construct(
        string $key,
        array $children = []
    ) {
        $this->setKey($key);
        $this->setChildren(...$children);
    }


    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return FacetNodeInterface[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    private function setKey(string $key): void
    {
        $this->key = $key;
    }

    private function setChildren(FacetNodeInterface ...$facetMembers): void
    {
        $this->children = $facetMembers;
    }
}
