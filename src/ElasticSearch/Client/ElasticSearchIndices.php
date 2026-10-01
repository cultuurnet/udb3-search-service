<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

interface ElasticSearchIndices
{
    public function exists(array $params): bool;

    public function existsAlias(array $params): bool;

    public function get(array $params): array;

    public function getAlias(array $params): array;

    public function putAlias(array $params): void;

    public function deleteAlias(array $params): void;

    public function create(array $params): void;

    public function delete(array $params): void;

    public function putMapping(array $params): void;

    public function putTemplate(array $params): void;
}
