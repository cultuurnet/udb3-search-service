<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class RelatedProductionRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $productionId = $parameterBagReader->getStringFromParameter('productionId', null);
        if (!is_null($productionId)) {
            $offerQueryBuilder = $offerQueryBuilder->withProductionIdFilter($productionId);
        }

        return $offerQueryBuilder;
    }
}
