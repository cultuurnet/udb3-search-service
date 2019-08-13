<?php

namespace CultuurNet\UDB3\Search\Place;

use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Search\AbstractSearchProjector;

class PlaceSearchProjector extends AbstractSearchProjector
{
    /**
     * @return array
     *
     * @uses handlePlaceProjectedToJSONLD
     * @uses handlePlaceDeleted
     */
    protected function getEventHandlers()
    {
        return [
            PlaceProjectedToJSONLD::class => 'handlePlaceProjectedToJSONLD',
        ];
    }

    /**
     * @param PlaceProjectedToJSONLD $placeProjectedToJSONLD
     */
    protected function handlePlaceProjectedToJSONLD(PlaceProjectedToJSONLD $placeProjectedToJSONLD)
    {
        $this->getIndexService()->index(
            $placeProjectedToJSONLD->getItemId(),
            $placeProjectedToJSONLD->getIri()
        );
    }
}
