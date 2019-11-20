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
                    $subscribers = $this->get('event_bus_subscribers');

                    if (!(is_null($this->parameter('config.event_bus'))) &&
                        !(is_null($this->parameter('config.event_bus.subscribers')))) {

                        $subscriberIds = $this->parameter('config.event_bus.subscribers');
                        $subscribers = [];
                        foreach ($subscriberIds as $subscriberServiceId) {
                            $subscribers[] = $this->get($subscriberServiceId);
                        }
                    }

                    foreach ($subscribers as $subscriber) {
                        $eventBus->subscribe($subscriber);
                    }

                });

                return $bus;
            }
        );
    }
}
