<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use CultureFeed_Consumer;
use Exception;
use ICultureFeed;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CultureFeedConsumerResolverTest extends TestCase
{
    private ConsumerResolver $consumerResolver;

    /**
     * @var ICultureFeed&MockObject
     */
    private $cultureFeed;

    protected function setUp(): void
    {
        $this->cultureFeed = $this->createMock(ICultureFeed::class);
        $this->consumerResolver = new CultureFeedConsumerResolver($this->cultureFeed);
    }

    /**
     * @test
     */
    public function it_handles_invalid_api_keys(): void
    {
        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_invalid_api_key', true)
            ->willThrowException(new Exception('Invalid API key'));

        $this->assertEquals(
            'INVALID',
            $this->consumerResolver->getStatus('my_invalid_api_key')
        );
    }

    /**
     * @test
     * @dataProvider validStatuses
     */
    public function it_handles_valid_api_keys(string $status): void
    {
        $cultureFeedConsumer = new CultureFeed_Consumer();
        $cultureFeedConsumer->status = $status;

        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_valid_api_key', true)
            ->willReturn($cultureFeedConsumer);

        $this->assertEquals(
            $status,
            $this->consumerResolver->getStatus('my_valid_api_key')
        );
    }

    /**
     * @test
     */
    public function it_handles_default_queries_from_uitid_v1(): void
    {
        $cultureFeedConsumer = new CultureFeed_Consumer();
        $cultureFeedConsumer->status = 'ACTIVE';
        $cultureFeedConsumer->searchPrefixSapi3 = 'my_default_search_query';

        $this->cultureFeed->expects($this->once())
            ->method('getServiceConsumerByApiKey')
            ->with('my_valid_api_key', true)
            ->willReturn($cultureFeedConsumer);

        $this->assertEquals(
            'my_default_search_query',
            $this->consumerResolver->getDefaultQuery('my_valid_api_key')
        );
    }

    public function validStatuses(): array
    {
        return [
            'blocked' => ['BLOCKED'],
            'removed' => ['REMOVED'],
            'active' => ['ACTIVE'],
        ];
    }
}
