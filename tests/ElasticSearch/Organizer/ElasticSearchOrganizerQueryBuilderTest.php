<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class ElasticSearchOrganizerQueryBuilderTest extends AbstractElasticSearchOrganizerQueryBuilderTest
{
    protected function createBuilder(int $aggregationSize = null): OrganizerQueryBuilderInterface
    {
        return new ElasticSearchOrganizerQueryBuilder($aggregationSize);
    }
}
