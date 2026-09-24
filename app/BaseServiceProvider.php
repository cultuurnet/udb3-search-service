<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Noodlehaus\Config;

abstract class BaseServiceProvider extends AbstractServiceProvider
{
    protected function add(string $serviceName, callable $function, ?string $tag = null): void
    {
        $definition = $this->getLeagueContainer()
            ->add($serviceName, $function);

        if ($tag !== null) {
            $definition->addTag($tag);
        }
    }

    protected function addShared(string $serviceName, callable $function, ?string $tag = null): void
    {
        $definition = $this->getLeagueContainer()
            ->share($serviceName, $function);

        if ($tag !== null) {
            $definition->addTag($tag);
        }
    }

    /**
     * Get parameter from config
     * @return mixed|null
     */
    protected function parameter(string $parameter)
    {
        return $this->getContainer()->get(Config::class)->get($parameter);
    }

    /**
     * Get service from container
     * @return mixed|null
     */
    protected function get(string $name)
    {
        return $this->getContainer()->get($name);
    }
}
