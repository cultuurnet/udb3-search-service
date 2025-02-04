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
        $this->clientIdResolver = $this->createMock(ClientIdResolver::class);
    }

    /**
     * @test
     * @dataProvider hasAccess
     */
    public function it_will_use_cached_values(bool $hasAccess): void
    {
        $cache = new ArrayAdapter();
        $cache->get(
            'my_cached_client_id',
            function () use ($hasAccess) {
                return $hasAccess;
            }
        );
        $this->cachedClientIdResolver = new CachedClientIdResolver(
            $cache,
            $this->clientIdResolver
        );
        $this->clientIdResolver->expects($this->never())
            ->method('hasSapiAccess');

        $result = $this->cachedClientIdResolver->hasSapiAccess('my_cached_client_id');
        $this->assertEquals($hasAccess, $result);
    }

    /**
     * @test
     * @dataProvider hasAccess
     */
    public function it_can_get_uncached_values_via_the_decoratee(bool $hasAccess): void
    {
        $this->cachedClientIdResolver = new CachedClientIdResolver(
            new ArrayAdapter(),
            $this->clientIdResolver
        );

        $this->clientIdResolver->expects($this->once())
            ->method('hasSapiAccess')
            ->willReturn($hasAccess);

        $result = $this->cachedClientIdResolver->hasSapiAccess('my_active_client_id');

        $this->assertEquals($hasAccess, $result);
    }

    public static function hasAccess(): array
    {
        return [
            'hasAccess' => [true],
            'hasNoAccess' => [false],
        ];
    }
}
