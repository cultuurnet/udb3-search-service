<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

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
