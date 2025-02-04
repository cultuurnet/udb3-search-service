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
            'my_cached_api_key',
            function () {
                return 'ACTIVE';
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
    public function it_will_use_cached_values(): void
    {
        $this->consumerResolver->expects($this->never())
            ->method('getStatus')
            ->with('my_cached_api_key')
            ->willReturn('ACTIVE');

        $this->assertEquals(
            'ACTIVE',
            $this->cachedConsumerResolver->getStatus('my_cached_api_key')
        );
    }

    /**
     * @test
     */
    public function it_can_get_uncached_values_via_the_decoratee(): void
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
}
