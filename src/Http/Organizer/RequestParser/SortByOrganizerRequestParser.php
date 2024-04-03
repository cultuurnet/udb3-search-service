<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use Psr\Http\Message\ServerRequestInterface;

final class SortByOrganizerRequestParser implements OrganizerRequestParser
{
    public function parse(
        ServerRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $parameters = $request->getQueryParams();
        $sorts = !empty($parameters['sort']) ? $parameters['sort'] : [];

        if (!is_array($sorts)) {
            throw new UnsupportedParameterValue('Invalid sorting syntax given.');
        }

        $sortBuilders = [
            'score' => function (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByScore($sortOrder);
            },
            'completeness' => function (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByCompleteness($sortOrder);
            },
            'created' => function (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByCreated($sortOrder);
            },
            'modified' => function (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByModified($sortOrder);
            },
        ];

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
            $organizerQueryBuilder = call_user_func($callback, $organizerQueryBuilder, $sortOrder);
        }

        return $organizerQueryBuilder;
    }
}
