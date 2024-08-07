<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Event;

use CultuurNet\UDB3\Search\AbstractSearchProjector;

final class EventSearchProjector extends AbstractSearchProjector
{
    /**
     *
     * @uses handleEventProjectedToJSONLD
     * @uses handleEventDeleted
     */
    protected function getEventHandlers(): array
    {
        return [
            EventProjectedToJSONLD::class => 'handleEventProjectedToJSONLD',
        ];
    }


    protected function handleEventProjectedToJSONLD(EventProjectedToJSONLD $eventProjectedToJSONLD): void
    {
        $this->getIndexService()->index(
            $eventProjectedToJSONLD->getItemId(),
            $eventProjectedToJSONLD->getIri()
        );
    }
}
