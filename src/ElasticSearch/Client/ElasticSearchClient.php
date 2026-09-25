<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

/**
 * Keeps the elasticsearch-php client's details out of the rest of the codebase: its
 * responses are objects that may be a Promise in async mode, and its Client is final.
 * Callers get plain arrays and booleans, and tests can mock this interface instead.
 */
interface ElasticSearchClient
{
    public function search(array $params): array;

    public function scroll(array $params): array;

    public function clearScroll(array $params): void;

    public function get(array $params): array;

    public function index(array $params): void;

    public function delete(array $params): void;

    public function bulk(array $params): void;

    public function indices(): ElasticSearchIndices;
}
