<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Predis\Client;

final class CacheProvider extends BaseServiceProvider
{
    protected $provides = [
        Client::class,
    ];

    public function register(): void
    {
        $this->add(Client::class, fn (): Client => new Client(
            $this->parameter('cache.redis')
        ));
    }
}
