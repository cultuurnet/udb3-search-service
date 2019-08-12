<?php

namespace CultuurNet\UDB3\SearchService\Event;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class EventControllerProvider implements ControllerProviderInterface
{
    /**
     * @var StringLiteral
     */
    private $regionIndexName;

    /**
     * @var StringLiteral
     */
    private $regionDocumentType;

    /**
     * @param StringLiteral $regionIndexName
     * @param StringLiteral $regionDocumentType
     */
    public function __construct(
        StringLiteral $regionIndexName,
        StringLiteral $regionDocumentType
    ) {
        $this->regionIndexName = $regionIndexName;
        $this->regionDocumentType = $regionDocumentType;
    }

    /**
     * @param Application $app
     * @return ControllerCollection
     */
    public function connect(Application $app)
    {
        $app['event_search_controller'] = $app->share(
            function (Application $app) {
                return $app['offer_search_controller_factory'](
                    $app['event_elasticsearch_service']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'event_search_controller:search');

        return $controllers;
    }
}
