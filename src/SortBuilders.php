<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;

final class SortBuilders
{
    /** @return OrganizerQueryBuilderInterface|OfferQueryBuilderInterface */
    public function build(array $sorts, array $sortBuilders, QueryBuilder $queryBuilder)
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
