<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Elastic\Elasticsearch\ClientInterface;
use Elastic\Elasticsearch\Endpoints\Indices;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Http\Promise\Promise;

/**
 * Extended Elasticsearch client interface that includes the actual API methods
 * that are only available on the concrete Client class via traits.app/ElasticSearchProvider.php
 */
interface ElasticSearchClientInterface extends ClientInterface
{
    public function search(array $params = []): Elasticsearch;
    public function bulk(array $params = []): Elasticsearch;
    public function index(array $params = []): Elasticsearch;
    public function delete(array $params = []): Elasticsearch;
    public function get(array $params = []): Elasticsearch;
    public function indices(): Indices;
    public function scroll(array $params = []): Elasticsearch;
    public function clearScroll(array $params = []) : Elasticsearch;
}
