<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy;

interface BulkIndexationStrategyInterface extends IndexationStrategyInterface
{
    public function flush(): void;
}
