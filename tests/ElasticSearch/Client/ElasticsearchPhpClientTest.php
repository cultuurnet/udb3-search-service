<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Client;

use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class ElasticsearchPhpClientTest extends TestCase
{
    private MockHandler $responses;

    private ?RequestInterface $lastRequest = null;

    private ElasticsearchPhpClient $client;

    protected function setUp(): void
    {
        $this->responses = new MockHandler();
        $handlerStack = HandlerStack::create($this->responses);
        $handlerStack->push(Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            $this->lastRequest = $request;
            return $request;
        }));

        $this->client = new ElasticsearchPhpClient(
            ClientBuilder::create()
                ->setHttpClient(new GuzzleClient(['handler' => $handlerStack]))
                ->build()
        );
    }

    /**
     * @test
     */
    public function it_returns_the_search_response_as_an_array(): void
    {
        $this->respondWith(200, ['hits' => ['hits' => []]]);

        $result = $this->client->search(['index' => 'udb3_core_read', 'body' => ['query' => ['match_all' => (object) []]]]);

        $this->assertSame(['hits' => ['hits' => []]], $result);
        $this->assertRequest('POST', '/udb3_core_read/_search');
    }

    /**
     * @test
     */
    public function it_returns_the_scroll_response_as_an_array(): void
    {
        $this->respondWith(200, ['_scroll_id' => 'abc']);

        $result = $this->client->scroll(['scroll_id' => 'abc', 'scroll' => '1m']);

        $this->assertSame(['_scroll_id' => 'abc'], $result);
        $this->assertRequest('GET', '/_search/scroll/abc');
    }

    /**
     * @test
     */
    public function it_clears_a_scroll(): void
    {
        $this->respondWith(200, ['succeeded' => true]);

        $this->client->clearScroll(['scroll_id' => 'abc']);

        $this->assertRequest('DELETE', '/_search/scroll/abc');
    }

    /**
     * @test
     */
    public function it_returns_a_document_as_an_array(): void
    {
        $this->respondWith(200, ['found' => true, '_source' => ['name' => 'foo']]);

        $result = $this->client->get(['index' => 'udb3_core_read', 'id' => '1']);

        $this->assertSame(['found' => true, '_source' => ['name' => 'foo']], $result);
        $this->assertRequest('GET', '/udb3_core_read/_doc/1');
    }

    /**
     * @test
     */
    public function it_indexes_a_document(): void
    {
        $this->respondWith(201, ['result' => 'created']);

        $this->client->index(['index' => 'udb3_core_write', 'id' => '1', 'body' => ['name' => 'foo']]);

        $this->assertRequest('PUT', '/udb3_core_write/_doc/1');
    }

    /**
     * @test
     */
    public function it_deletes_a_document(): void
    {
        $this->respondWith(200, ['result' => 'deleted']);

        $this->client->delete(['index' => 'udb3_core_write', 'id' => '1']);

        $this->assertRequest('DELETE', '/udb3_core_write/_doc/1');
    }

    /**
     * @test
     */
    public function it_sends_a_bulk_request(): void
    {
        $this->respondWith(200, ['errors' => false, 'items' => []]);

        $this->client->bulk([
            'body' => [
                ['index' => ['_index' => 'udb3_core_write', '_id' => '1']],
                ['name' => 'foo'],
            ],
        ]);

        $this->assertRequest('POST', '/_bulk');
    }

    /**
     * @test
     */
    public function it_returns_whether_an_index_exists(): void
    {
        $this->respondWith(200);
        $this->respondWith(404);

        $this->assertTrue($this->client->indices()->exists(['index' => 'udb3_core_v1']));
        $this->assertFalse($this->client->indices()->exists(['index' => 'udb3_core_v1']));
        $this->assertRequest('HEAD', '/udb3_core_v1');
    }

    /**
     * @test
     */
    public function it_returns_whether_an_alias_exists(): void
    {
        $this->respondWith(200);
        $this->respondWith(404);

        $this->assertTrue($this->client->indices()->existsAlias(['name' => 'udb3_core_read']));
        $this->assertFalse($this->client->indices()->existsAlias(['name' => 'udb3_core_read']));
        $this->assertRequest('HEAD', '/_alias/udb3_core_read');
    }

    /**
     * @test
     */
    public function it_returns_an_index_as_an_array(): void
    {
        $this->respondWith(200, ['udb3_core_v1' => ['aliases' => []]]);

        $result = $this->client->indices()->get(['index' => 'udb3_core_read']);

        $this->assertSame(['udb3_core_v1' => ['aliases' => []]], $result);
        $this->assertRequest('GET', '/udb3_core_read');
    }

    /**
     * @test
     */
    public function it_returns_the_aliases_as_an_array(): void
    {
        $this->respondWith(200, ['udb3_core_v1' => ['aliases' => ['udb3_core_read' => []]]]);

        $result = $this->client->indices()->getAlias(['name' => 'udb3_core_read']);

        $this->assertSame(['udb3_core_v1' => ['aliases' => ['udb3_core_read' => []]]], $result);
        $this->assertRequest('GET', '/_alias/udb3_core_read');
    }

    /**
     * @test
     */
    public function it_puts_an_alias(): void
    {
        $this->respondWith(200, ['acknowledged' => true]);

        $this->client->indices()->putAlias(['index' => 'udb3_core_v1', 'name' => 'udb3_core_read']);

        $this->assertRequest('PUT', '/udb3_core_v1/_alias/udb3_core_read');
    }

    /**
     * @test
     */
    public function it_deletes_an_alias(): void
    {
        $this->respondWith(200, ['acknowledged' => true]);

        $this->client->indices()->deleteAlias(['index' => 'udb3_core_v1', 'name' => 'udb3_core_read']);

        $this->assertRequest('DELETE', '/udb3_core_v1/_alias/udb3_core_read');
    }

    /**
     * @test
     */
    public function it_creates_an_index(): void
    {
        $this->respondWith(200, ['acknowledged' => true]);

        $this->client->indices()->create(['index' => 'udb3_core_v1']);

        $this->assertRequest('PUT', '/udb3_core_v1');
    }

    /**
     * @test
     */
    public function it_deletes_an_index(): void
    {
        $this->respondWith(200, ['acknowledged' => true]);

        $this->client->indices()->delete(['index' => 'udb3_core_v1']);

        $this->assertRequest('DELETE', '/udb3_core_v1');
    }

    /**
     * @test
     */
    public function it_puts_a_mapping(): void
    {
        $this->respondWith(200, ['acknowledged' => true]);

        $this->client->indices()->putMapping(['index' => 'udb3_core_v1', 'body' => ['properties' => []]]);

        $this->assertRequest('PUT', '/udb3_core_v1/_mapping');
    }

    /**
     * @test
     */
    public function it_puts_a_template(): void
    {
        $this->respondWith(200, ['acknowledged' => true]);

        $this->client->indices()->putTemplate(['name' => 'autocomplete_analyzer', 'body' => ['index_patterns' => ['*']]]);

        $this->assertRequest('PUT', '/_template/autocomplete_analyzer');
    }

    private function respondWith(int $status, ?array $body = null): void
    {
        $this->responses->append(
            new Response(
                $status,
                [
                    // The client refuses any response that doesn't identify itself as coming from Elasticsearch.
                    'X-Elastic-Product' => 'Elasticsearch',
                    'Content-Type' => 'application/json',
                ],
                $body === null ? '' : (string) json_encode($body)
            )
        );
    }

    private function assertRequest(string $method, string $path): void
    {
        $this->assertSame(
            [$method, $path],
            [$this->lastRequest?->getMethod(), $this->lastRequest?->getUri()->getPath()]
        );
    }
}
