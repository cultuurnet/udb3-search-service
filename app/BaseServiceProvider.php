<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Noodlehaus\Config;

abstract class BaseServiceProvider extends AbstractServiceProvider
{
    /**
     * Add Service definition to container
     *
     * @param string $serviceName
     * @param $function
     * @param string|null $tag
     */
    protected function add(string $serviceName, $function, ?string $tag = null)
    {
        $definition = $this->getLeagueContainer()
            ->add($serviceName, $function);

        if ($tag !== null) {
            $definition->addTag($tag);
        }
    }

    /**
     * Get parameter from config
     *
     * @param string $parameter
     * @return mixed
     */
    protected function parameter(string $parameter)
    {
        return $this->getContainer()->get(Config::class)->get($parameter);
    }

    /**
     * Get service from container
     *
     * @param string $name
     * @return mixed
     */
    protected function get(string $name)
    {
        return $this->getContainer()->get($name);
    }
}
