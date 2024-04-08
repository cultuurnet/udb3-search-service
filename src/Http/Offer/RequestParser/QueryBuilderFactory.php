<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\QueryBuilder;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class QueryBuilderFactory
{
    /** @return OrganizerQueryBuilderInterface|OfferQueryBuilderInterface */
    public static function getQueryBuilder(array $sorts, array $sortBuilders, QueryBuilder $queryBuilder)
    {
        foreach ($sorts as $field => $order) {
            if (!isset($sortBuilders[$field])) {
                throw new UnsupportedParameterValue("Invalid sort field '{$field}' given.");
            }

            try {
                $sortOrder = new SortOrder($order);
            } catch (UnsupportedParameterValue $e) {
                throw new UnsupportedParameterValue("Invalid sort order '{$order}' given.");
            }

            $callback = $sortBuilders[$field];
            $queryBuilder = $callback($queryBuilder, $sortOrder);
        }

        return $queryBuilder;
    }
}
