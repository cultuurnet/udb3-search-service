<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

trait ElasticSearch5Compatibility
{
    protected bool $typeEnabled = false;

    public function enableType(): static
    {
        $this->typeEnabled = true;
        return $this;
    }
}
