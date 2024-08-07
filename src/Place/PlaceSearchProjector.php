<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Place;

use CultuurNet\UDB3\Search\AbstractSearchProjector;

final class PlaceSearchProjector extends AbstractSearchProjector
{
    /**
     *
     * @uses handlePlaceProjectedToJSONLD
     * @uses handlePlaceDeleted
     */
    protected function getEventHandlers(): array
    {
        return [
            PlaceProjectedToJSONLD::class => 'handlePlaceProjectedToJSONLD',
        ];
    }


    protected function handlePlaceProjectedToJSONLD(PlaceProjectedToJSONLD $placeProjectedToJSONLD): void
    {
        $this->getIndexService()->index(
            $placeProjectedToJSONLD->getItemId(),
            $placeProjectedToJSONLD->getIri()
        );
    }
}
