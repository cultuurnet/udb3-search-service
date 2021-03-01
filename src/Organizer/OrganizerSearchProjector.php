<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Organizer;

use CultuurNet\UDB3\Search\AbstractSearchProjector;

class OrganizerSearchProjector extends AbstractSearchProjector
{
    /**
     * @return array
     *
     * @uses handleOrganizerProjectedToJSONLD
     * @uses handleOrganizerDeleted
     */
    protected function getEventHandlers()
    {
        return [
            OrganizerProjectedToJSONLD::class => 'handleOrganizerProjectedToJSONLD',
        ];
    }


    protected function handleOrganizerProjectedToJSONLD(OrganizerProjectedToJSONLD $organizerProjectedToJSONLD)
    {
        $this->getIndexService()->index(
            $organizerProjectedToJSONLD->getId(),
            $organizerProjectedToJSONLD->getIri()
        );
    }
}
