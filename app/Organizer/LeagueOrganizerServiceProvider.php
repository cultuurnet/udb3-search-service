<?php

namespace CultuurNet\UDB3\SearchService\Organizer;

use CultuurNet\UDB3\Search\Organizer\OrganizerSearchServiceInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;

class LeagueOrganizerServiceProvider extends AbstractServiceProvider
{
    protected $provides = [
        OrganizerSearchServiceInterface::class,
    ];

    public function register()
    {
        $this->leagueContainer->add(
            OrganizerSearchServiceInterface::class,
            function () {
                // return implementation
            }
        );
    }
}
