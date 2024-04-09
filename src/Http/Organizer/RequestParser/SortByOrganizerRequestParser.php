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
            'score' => fn (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OrganizerQueryBuilderInterface
                => $queryBuilder->withSortByScore($sortOrder),
            'completeness' => fn (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OrganizerQueryBuilderInterface
                => $queryBuilder->withSortByCompleteness($sortOrder),
            'created' => fn (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OrganizerQueryBuilderInterface
                => $queryBuilder->withSortByCreated($sortOrder),
            'modified' => fn (OrganizerQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OrganizerQueryBuilderInterface
                => $queryBuilder->withSortByModified($sortOrder),
        ];

        return $organizerQueryBuilder->withSortBuilders($sorts, $sortBuilders);
    }
}
