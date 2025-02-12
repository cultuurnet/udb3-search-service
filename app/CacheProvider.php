<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Predis\Client;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\CacheInterface;

final class CacheProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
        CacheInterface::class,
    ];

    public function register(): void
    {
        $this->add(Client::class, fn (): Client => new Client(
            $this->parameter('cache.redis')
        ));

        $this->add(
            CacheInterface::class,
            fn (): CacheInterface =>
            new RedisAdapter(
                $this->get(Client::class),
                'permission',
                86400,
            ),
        );
    }
}
