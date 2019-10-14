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
        $this->add(
            EventBusInterface::class,
            function () {
                $bus = new SimpleEventBus();
                $bus->beforeFirstPublication(function (EventBusInterface $eventBus) {
                    $subscribers = [
                        'organizer_search_projector',
                        'event_search_projector',
                        'place_search_projector',
                    ];
                    
                    if (!(is_null($this->parameter('config.event_bus'))) &&
                        !(is_null($this->parameter('config.event_bus.subscribers')))) {
                        
                        $subscribers = $this->parameter('config.event_bus.subscribers');
                    }
                    
                    foreach ($subscribers as $subscriberServiceId) {
                        $eventBus->subscribe($this->get($subscriberServiceId));
                    }
                    
                });
                
                return $bus;
            }
        );
    }
}
