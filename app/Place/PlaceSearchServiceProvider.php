<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Place;

use CultuurNet\UDB3\Search\Http\OfferSearchController;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;
use CultuurNet\UDB3\SearchService\Offer\OfferSearchControllerFactory;

final class PlaceSearchServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        'place_controller',
    ];

    public function register(): void
    {
        $this->add(
            'place_controller',
            function (): OfferSearchController {
                /** @var OfferSearchControllerFactory $offerControllerFactory */
                $offerControllerFactory = $this->get(OfferSearchControllerFactory::class);

                return $offerControllerFactory->createFor(
                    $this->parameter('elasticsearch.place.read_index'),
                    $this->parameter('elasticsearch.place.document_type')
                );
            }
        );
    }
}
