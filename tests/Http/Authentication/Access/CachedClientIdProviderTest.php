<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CachedClientIdProviderTest extends TestCase
{
    /**
     * @var ClientIdProvider&MockObject
     */
    private $clientIdProvider;

    private CachedClientIdProvider $cachedClientIdProvider;

    protected function setUp(): void
    {
        $this->clientIdProvider = $this->createMock(ClientIdProvider::class);
    }

    /**
     * @test
     * @dataProvider hasAccess
     */
    public function it_will_use_cached_values(bool $hasAccess): void
    {
        $this->cachedClientIdProvider = new CachedClientIdProvider(
            new InMemoryCache([
                'my_cached_client_id' => $hasAccess,
            ]),
            $this->clientIdProvider
        );
        $this->clientIdProvider->expects($this->never())
            ->method('hasSapiAccess');

        $result = $this->cachedClientIdProvider->hasSapiAccess('my_cached_client_id');

        // Assert that the result matches the expected outcome
        $this->assertEquals($hasAccess, $result);
    }

    /**
     * @test
     * @dataProvider hasAccess
     */
    public function it_can_get_uncached_values_via_the_decoratee(bool $hasAccess): void
    {
        $this->cachedClientIdProvider = new CachedClientIdProvider(
            new InMemoryCache([]),
            $this->clientIdProvider
        );

        $this->clientIdProvider->expects($this->once())
            ->method('hasSapiAccess')
            ->willReturn($hasAccess);

        $result = $this->cachedClientIdProvider->hasSapiAccess('my_active_client_id');

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
