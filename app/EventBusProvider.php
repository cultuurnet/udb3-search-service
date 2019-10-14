<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Search\SimpleEventBus;

class EventBusProvider extends BaseServiceProvider
{
    
    protected $provides = [
        EventBusInterface::class,
    ];
    
    /**
     * Use the register method to register items with the container via the
     * protected $this->leagueContainer property or the `getLeagueContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->add(EventBusInterface::class,
            function () {
                $bus = new SimpleEventBus();
                // @TODO: add subscribers
                return $bus;
            }
        );
    }
}
