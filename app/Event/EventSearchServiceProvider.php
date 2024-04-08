<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Event;

use CultuurNet\UDB3\Search\Http\OfferSearchController;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferSearchControllerFactory;

final class EventSearchServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        'event_controller',
    ];

    public function register(): void
    {
        $this->add(
            'event_controller',
            function (): OfferSearchController {
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
