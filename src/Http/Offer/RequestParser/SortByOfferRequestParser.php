<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\SortOrder;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class SortByOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $sorts = $request->getQueryParam('sort', []);

        if (!is_array($sorts)) {
            throw new UnsupportedParameterValue('Invalid sorting syntax given.');
        }

        $sortBuilders = [
            'score' => fn (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OfferQueryBuilderInterface
                => $queryBuilder->withSortByScore($sortOrder),
            'completeness' => fn (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OfferQueryBuilderInterface
                => $queryBuilder->withSortByCompleteness($sortOrder),
            'availableTo' => fn (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OfferQueryBuilderInterface
                => $queryBuilder->withSortByAvailableTo($sortOrder),
            'distance' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) use ($request): OfferQueryBuilderInterface {
                $coordinates = $request->getQueryParam('coordinates', false);
                if (!$coordinates) {
                    throw new MissingParameter(
                        'Required "coordinates" parameter missing when sorting by distance.'
                    );
                }

                $coordinates = Coordinates::fromLatLonString($coordinates);
                return $queryBuilder->withSortByDistance($coordinates, $sortOrder);
            },
            'created' => fn (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OfferQueryBuilderInterface
                => $queryBuilder->withSortByCreated($sortOrder),
            'modified' => fn (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OfferQueryBuilderInterface
                => $queryBuilder->withSortByModified($sortOrder),
            'popularity' => fn (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder): OfferQueryBuilderInterface
                => $queryBuilder->withSortByPopularity($sortOrder),
            'recommendationScore' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) use ($request): OfferQueryBuilderInterface {
                $recommendationFor = $request->getQueryParam('recommendationFor', false);
                if (!$recommendationFor) {
                    throw new MissingParameter(
                        'Required "recommendationFor" parameter missing when sorting by recommendation score.'
                    );
                }
                return $queryBuilder->withSortByRecommendationScore($recommendationFor, $sortOrder);
            },
        ];

        return $offerQueryBuilder->withSortBuilders($sorts, $sortBuilders);
    }
}
