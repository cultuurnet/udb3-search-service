<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

interface BuilderInterface
{
    public function toArray(): array;
}
