<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

final class CachedClientIdProviderTest extends TestCase
{
    /**
     * @var RedisAdapter&MockObject
     */
    private $cache;

    /**
     * @var ClientIdProvider&MockObject
     */
    private $clientIdProvider;

    /**
     * @var CacheItemInterface&MockObject
     */
    private $cacheItem;

    private CachedClientIdProvider $cachedClientIdProvider;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(RedisAdapter::class);
        $this->clientIdProvider = $this->createMock(ClientIdProvider::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->cachedClientIdProvider = new CachedClientIdProvider(
            $this->cache,
            $this->clientIdProvider
        );
    }

    /**
     * @test
     * @dataProvider hasAccess
     */
    public function it_will_use_cached_values(bool $hasAccess): void
    {
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($hasAccess);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('my_active_client_id')
            ->willReturn($this->cacheItem);

        $this->clientIdProvider->expects($this->never())
            ->method('hasSapiAccess');

        $this->cacheItem->expects($this->never())
            ->method('set');

        $this->assertEquals(
            $hasAccess,
            $this->cachedClientIdProvider->hasSapiAccess('my_active_client_id')
        );
    }

    /**
     * @test
     * @dataProvider hasAccess
     */
    public function it_will_save_values_in_the_cache(bool $hasAccess): void
    {
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $this->cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($hasAccess);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('my_active_client_id')
            ->willReturn($this->cacheItem);

        $this->clientIdProvider->expects($this->once())
            ->method('hasSapiAccess')
            ->willReturn($hasAccess);

        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with($hasAccess);

        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);

        $this->assertEquals(
            $hasAccess,
            $this->cachedClientIdProvider->hasSapiAccess('my_active_client_id')
        );
    }

    public static function hasAccess(): array
    {
        return [
            'hasAccess' => [true],
            'hasNoAccess' => [false],
        ];
    }
}
