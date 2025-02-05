<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedConsumerResolverTest extends TestCase
{
    /**
     * @var ConsumerResolver&MockObject
     */
    private $consumerResolver;

    private CachedConsumerResolver $cachedConsumerResolver;

    public function setUp(): void
    {
        $cache = new ArrayAdapter();
        $cache->get(
            'consumer_id_my_cached_api_key_status',
            function () {
                return 'ACTIVE';
            }
        );
        $cache->get(
            'consumer_id_my_cached_api_key_query',
            function () {
                return 'my_cached_query';
            }
        );
        $this->consumerResolver = $this->createMock(ConsumerResolver::class);

        $this->cachedConsumerResolver = new CachedConsumerResolver(
            $cache,
            $this->consumerResolver
        );
    }

    /**
     * @test
     */
    public function it_will_get_the_cached_status_value(): void
    {
        $this->consumerResolver->expects($this->never())
            ->method('getStatus')
            ->with('my_cached_api_key');

        $this->assertEquals(
            'ACTIVE',
            $this->cachedConsumerResolver->getStatus('my_cached_api_key')
        );
    }

    /**
     * @test
     */
    public function it_will_get_the_cached_query_value(): void
    {
        $this->consumerResolver->expects($this->never())
            ->method('getDefaultQuery')
            ->with('my_cached_api_key');

        $this->assertEquals(
            'my_cached_query',
            $this->cachedConsumerResolver->getDefaultQuery('my_cached_api_key')
        );
    }

    /**
     * @test
     */
    public function it_can_get_uncached_statuses_via_the_decoratee(): void
    {
        $this->consumerResolver->expects($this->once())
            ->method('getStatus')
            ->with('my_valid_api_key')
            ->willReturn('ACTIVE');

        $this->assertEquals(
            'ACTIVE',
            $this->cachedConsumerResolver->getStatus('my_valid_api_key')
        );
    }

    /**
     * @test
     */
    public function it_can_get_uncached_queries_via_the_decoratee(): void
    {
        $this->consumerResolver->expects($this->once())
            ->method('getDefaultQuery')
            ->with('my_valid_api_key')
            ->willReturn('my_default_query');

        $this->assertEquals(
            'my_default_query',
            $this->cachedConsumerResolver->getDefaultQuery('my_valid_api_key')
        );
    }
}
