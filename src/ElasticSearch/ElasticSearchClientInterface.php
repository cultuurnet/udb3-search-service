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
    public function search(array $params = []): Elasticsearch|Promise;
    public function bulk(array $params = []): Elasticsearch|Promise;
    public function index(array $params = []): Elasticsearch|Promise;
    public function delete(array $params = []): Elasticsearch|Promise;
    public function get(array $params = []): Elasticsearch|Promise;
    public function indices(): Indices;
    public function scroll(array $params = []): Elasticsearch|Promise;
    public function clearScroll(array $params = []) : Elasticsearch|Promise;
}
