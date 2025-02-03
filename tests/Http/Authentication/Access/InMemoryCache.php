<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use Symfony\Contracts\Cache\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    private array $cache = [];

    public function __construct(array $initialValues = [])
    {
        $this->cache = $initialValues;
    }

    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null)
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $value = $callback($this);
        $this->cache[$key] = $value;

        return $value;
    }

    public function delete(string $key): bool
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            return true;
        }
        return false;
    }
}
