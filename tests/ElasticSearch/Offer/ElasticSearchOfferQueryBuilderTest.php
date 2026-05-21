<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class ElasticSearchOfferQueryBuilderTest extends AbstractElasticSearchOfferQueryBuilderTest
{
    protected function createBuilder(int $aggregationSize = null): OfferQueryBuilderInterface
    {
        return new ElasticSearchOfferQueryBuilder($aggregationSize);
    }
}
