<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchClient;
use Psr\Log\LoggerInterface;

final class ReplaceLocationSuggestionsIndexTest extends AbstractOperationTestCase
{
    protected function createOperation(ElasticSearchClient $client, LoggerInterface $logger): ReplaceLocationSuggestionsIndex
    {
        return new ReplaceLocationSuggestionsIndex($client, $logger);
    }

    /**
     * @test
     */
    public function it_replaces_its_own_index_in_the_alias_in_one_request_and_deletes_its_other_indices(): void
    {
        $this->indices->expects($this->once())
            ->method('create')
            ->with($this->callback(
                fn (array $params): bool => $params['index'] === 'addresses_new'
                    && isset($params['body']['settings']['analysis'], $params['body']['mappings']['properties'])
            ));

        $this->client->expects($this->once())
            ->method('bulk')
            ->with([
                'body' => [
                    ['index' => ['_index' => 'addresses_new', '_id' => 'street:1']],
                    ['type' => 'street'],
                ],
                'refresh' => true,
            ]);

        $this->client->method('search')->willReturn(['hits' => ['total' => ['value' => 1]]]);

        $this->indices->method('get')->willReturnMap([
            [['index' => 'alias'], ['addresses_live' => [], 'places' => []]],
            [['index' => 'addresses_*'], ['addresses_live' => [], 'addresses_left_behind' => [], 'addresses_new' => []]],
        ]);

        $this->indices->expects($this->once())
            ->method('updateAliases')
            ->with(['body' => ['actions' => [
                ['remove' => ['index' => 'addresses_live', 'alias' => 'alias']],
                ['add' => ['index' => 'addresses_new', 'alias' => 'alias']],
            ]]]);

        $deleted = [];
        $this->indices->method('delete')->willReturnCallback(
            function (array $params) use (&$deleted): void {
                $deleted[] = $params['index'];
            }
        );

        $this->operation->run('addresses_new', 'addresses_*', 'alias', ['street:1' => ['type' => 'street']], 1, 0);

        $this->assertSame(['addresses_live', 'addresses_left_behind'], $deleted);
    }

    /**
     * @test
     */
    public function it_deletes_the_new_index_and_leaves_the_alias_when_indexing_fails(): void
    {
        $this->client->method('bulk')->willThrowException(new \RuntimeException('Bulk failed'));

        $this->indices->expects($this->once())
            ->method('delete')
            ->with(['index' => 'new_index']);

        $this->indices->expects($this->never())->method('updateAliases');

        $this->expectException(\RuntimeException::class);

        $this->operation->run('new_index', 'addresses_*', 'alias', ['street:1' => ['type' => 'street']], 1, 0);
    }

    /**
     * @test
     */
    public function it_leaves_the_alias_when_the_index_holds_fewer_documents_than_were_sent(): void
    {
        $this->client->method('search')->willReturn(['hits' => ['total' => ['value' => 0]]]);

        $this->indices->expects($this->once())
            ->method('delete')
            ->with(['index' => 'new_index']);

        $this->indices->expects($this->never())->method('updateAliases');

        $this->expectExceptionMessage('Sent 1 documents but new_index holds 0.');

        $this->operation->run('new_index', 'addresses_*', 'alias', ['street:1' => ['type' => 'street']], 1, 0);
    }
}
