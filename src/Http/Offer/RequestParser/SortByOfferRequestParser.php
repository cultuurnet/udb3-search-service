<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\SortOrder;

class SortByOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $sorts = $request->getQueryParam('sort', []);

        if (!is_array($sorts)) {
            throw new \InvalidArgumentException('Invalid sorting syntax given.');
        }

        $sortBuilders = [
            'score' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByScore($sortOrder);
            },
            'availableTo' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByAvailableTo($sortOrder);
            },
            'distance' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) use ($request) {
                $coordinates = $request->getQueryParam('coordinates', false);
                if (!$coordinates) {
                    throw new \InvalidArgumentException(
                        'Required "coordinates" parameter missing when sorting by distance.'
                    );
                }

                $coordinates = Coordinates::fromLatLonString($coordinates);
                return $queryBuilder->withSortByDistance($coordinates, $sortOrder);
            },
            'created' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByCreated($sortOrder);
            },
            'modified' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByModified($sortOrder);
            },
            'popularity' => function (OfferQueryBuilderInterface $queryBuilder, SortOrder $sortOrder) {
                return $queryBuilder->withSortByPopularity($sortOrder);
            },
        ];

        foreach ($sorts as $field => $order) {
            if (!isset($sortBuilders[$field])) {
                throw new \InvalidArgumentException("Invalid sort field '{$field}' given.");
            }

            try {
                $sortOrder = SortOrder::get($order);
            } catch (\InvalidArgumentException $e) {
                throw new \InvalidArgumentException("Invalid sort order '{$order}' given.");
            }

            $callback = $sortBuilders[$field];
            $offerQueryBuilder = call_user_func($callback, $offerQueryBuilder, $sortOrder);
        }

        return $offerQueryBuilder;
    }
}
