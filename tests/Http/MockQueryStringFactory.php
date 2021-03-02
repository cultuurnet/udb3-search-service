<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\QueryStringFactoryInterface;

final class MockQueryStringFactory implements QueryStringFactoryInterface
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
