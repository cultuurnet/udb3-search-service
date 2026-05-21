<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\DSL;

interface NamedBuilderInterface extends BuilderInterface
{
    public function getName(): string;
}
