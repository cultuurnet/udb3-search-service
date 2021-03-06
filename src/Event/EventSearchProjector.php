<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Event;

use CultuurNet\UDB3\Search\AbstractSearchProjector;

final class EventSearchProjector extends AbstractSearchProjector
{
    /**
     * @return array
     *
     * @uses handleEventProjectedToJSONLD
     * @uses handleEventDeleted
     */
    protected function getEventHandlers()
    {
        return [
            EventProjectedToJSONLD::class => 'handleEventProjectedToJSONLD',
        ];
    }


    protected function handleEventProjectedToJSONLD(EventProjectedToJSONLD $eventProjectedToJSONLD)
    {
        $this->getIndexService()->index(
            $eventProjectedToJSONLD->getItemId(),
            $eventProjectedToJSONLD->getIri()
        );
    }
}
