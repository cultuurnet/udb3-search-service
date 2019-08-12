<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson;

class CopyJsonCombination implements CopyJsonInterface
{
    /**
     * @var CopyJsonInterface[]
     */
    private $copiers = [];

    public function __construct(CopyJsonInterface... $copiers)
    {
        $this->copiers = $copiers;
    }

    /**
     * @param \stdClass $from
     * @param \stdClass $to
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        foreach ($this->copiers as $copier) {
            $copier->copy($from, $to);
        }
    }
}
