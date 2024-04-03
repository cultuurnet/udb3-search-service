<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\QueryStringFactory;

final class MockQueryStringFactory implements QueryStringFactory
{
    /**
     * @return MockQueryString
     */
    public function fromString(string $queryString)
    {
        return new MockQueryString($queryString);
    }
}
