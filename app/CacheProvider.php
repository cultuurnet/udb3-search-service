<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Predis\Client;

final class CacheProvider extends BaseServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            Client::class,
        ], true);
    }

    public function register(): void
    {
        $this->add(Client::class, fn (): Client => new Client(
            $this->parameter('cache.redis')
        ));
    }
}
