<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

interface QueryStringFactory
{
    /**
     * @return AbstractQueryString
     */
    public function fromString(string $queryString);
}
