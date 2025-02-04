<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CachedClientIdResolverTest extends TestCase
{
    /**
     * @var ClientIdResolver&MockObject
     */
    private $clientIdResolver;

    private CachedClientIdResolver $cachedClientIdResolver;

    protected function setUp(): void
    {
        $cache = new ArrayAdapter();
        $cache->get(
            'my_cached_client_id',
            function () {
                return true;
            }
        );
        $this->clientIdResolver = $this->createMock(ClientIdResolver::class);
        $this->cachedClientIdResolver = new CachedClientIdResolver(
            $cache,
            $this->clientIdResolver
        );
    }

    /**
     * @test
     */
    public function it_will_use_cached_values(): void
    {
        $this->clientIdResolver->expects($this->never())
            ->method('hasSapiAccess');

        $this->assertTrue($this->cachedClientIdResolver->hasSapiAccess('my_cached_client_id'));
    }

    /**
     * @test
     */
    public function it_can_get_uncached_values_via_the_decoratee(): void
    {
        $this->cachedClientIdResolver = new CachedClientIdResolver(
            new ArrayAdapter(),
            $this->clientIdResolver
        );

        $this->clientIdResolver->expects($this->once())
            ->method('hasSapiAccess')
            ->willReturn(true);

        $this->assertTrue($this->cachedClientIdResolver->hasSapiAccess('my_active_client_id'));
    }
}
