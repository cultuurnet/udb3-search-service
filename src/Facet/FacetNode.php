<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

use CultuurNet\UDB3\Search\Language\MultilingualString;

final class FacetNode extends AbstractFacetTree
{
    private MultilingualString $name;

    private int $count;

    public function __construct(
        string $key,
        MultilingualString $name,
        int $count,
        array $children = []
    ) {
        parent::__construct($key, $children);
        $this->name = $name;
        $this->setcount($count);
    }


    public function getName(): MultilingualString
    {
        return $this->name;
    }


    public function getCount(): int
    {
        return $this->count;
    }

    private function setCount(int $count): void
    {
        $this->count = $count;
    }
}
