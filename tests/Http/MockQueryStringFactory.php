<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\QueryStringFactory;

final class MockQueryStringFactory implements QueryStringFactory
{
    /**
     * @param string $queryString
     * @return MockQueryString
     */
    public function fromString($queryString)
    {
        return new MockQueryString($queryString);
    }
}
