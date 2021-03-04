<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Facet;

use CultuurNet\UDB3\Search\Language\MultilingualString;

final class FacetNode extends AbstractFacetTree
{
    /**
     * @var MultilingualString
     */
    private $name;

    /**
     * @var int
     */
    private $count;

    /**
     * @param string $key
     * @param int $count
     */
    public function __construct(
        $key,
        MultilingualString $name,
        $count,
        array $children = []
    ) {
        parent::__construct($key, $children);
        $this->name = $name;
        $this->setcount($count);
    }

    /**
     * @return MultilingualString
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    private function setCount($count)
    {
        if (!is_int($count)) {
            throw new \InvalidArgumentException('Facet node count should be a int.');
        }
        $this->count = $count;
    }
}
