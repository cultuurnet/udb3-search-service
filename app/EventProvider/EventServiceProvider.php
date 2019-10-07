<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\EventProvider;

use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\SearchService\Factory\OfferSearchControllerFactory;

class EventServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        'event_controller'
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
        $this->add('event_controller',
            function () {
                /** @var OfferSearchControllerFactory $offerControllerFactory */
                $offerControllerFactory = $this->get(OfferSearchControllerFactory::class);
                
                return $offerControllerFactory->createFor(
                    $this->parameter('elasticsearch.event.read_index'),
                    $this->parameter('elasticsearch.event.document_type')
                );
            }
        );
    }
}
