<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

interface QueryStringFactoryInterface
{
    /**
     * @param string $queryString
     * @return AbstractQueryString
     */
    public function fromString($queryString);
}
