<?php

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\SortOrder;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class SortByOrganizerRequestParser implements OrganizerRequestParser
{
    public function parse(
        Request $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $sorts = $request->query->get('sort', []);

        if (!is_array($sorts)) {
            throw new InvalidArgumentException('Invalid sorting syntax given.');
        }

        $sortBuilders = [
            'score' => function (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByScore($sortOrder);
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
                throw new InvalidArgumentException("Invalid sort field '{$field}' given.");
            }

            try {
                $sortOrder = SortOrder::get($order);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException("Invalid sort order '{$order}' given.");
            }

            $callback = $sortBuilders[$field];
            $organizerQueryBuilder = call_user_func($callback, $organizerQueryBuilder, $sortOrder);
        }

        return $organizerQueryBuilder;
    }
}