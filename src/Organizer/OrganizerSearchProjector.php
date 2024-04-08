<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Organizer;

use CultuurNet\UDB3\Search\AbstractSearchProjector;

final class OrganizerSearchProjector extends AbstractSearchProjector
{
    /**
     *
     * @uses handleOrganizerProjectedToJSONLD
     * @uses handleOrganizerDeleted
     */
    protected function getEventHandlers(): array
    {
        return [
            OrganizerProjectedToJSONLD::class => 'handleOrganizerProjectedToJSONLD',
        ];
    }


    protected function handleOrganizerProjectedToJSONLD(OrganizerProjectedToJSONLD $organizerProjectedToJSONLD): void
    {
        $this->getIndexService()->index(
            $organizerProjectedToJSONLD->getId(),
            $organizerProjectedToJSONLD->getIri()
        );
    }
}
