<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\GeoBoundsParametersFactory;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class GeoBoundsOfferRequestParser implements OfferRequestParserInterface
{
    private GeoBoundsParametersFactory $geoBoundsParametersFactory;

    public function __construct(GeoBoundsParametersFactory $geoBoundsParametersFactory)
    {
        $this->geoBoundsParametersFactory = $geoBoundsParametersFactory;
    }

    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $geoBoundsParameters = $this->geoBoundsParametersFactory->fromApiRequest($request);

        if ($geoBoundsParameters === null) {
            return $offerQueryBuilder;
        }

        return $offerQueryBuilder->withGeoBoundsFilter($geoBoundsParameters);
    }
}
