<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Predis\Client;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheProvider extends BaseServiceProvider
{
    protected $provides = [
        CacheInterface::class,
    ];

    public function register(): void
    {
        $this->add(
            CacheInterface::class,
            fn (): CacheInterface =>
            new RedisAdapter(
                new Client(
                    $this->parameter('cache.redis')
                ),
                'permission' . '_',
                86400,
            ),
        );
    }
}
