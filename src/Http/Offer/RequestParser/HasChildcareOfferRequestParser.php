<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class HasChildcareOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $hasChildcare = $parameterBagReader->getBooleanFromParameter('hasChildcare');

        if (!is_null($hasChildcare)) {
            $offerQueryBuilder = $offerQueryBuilder->withHasChildcareFilter($hasChildcare);
        }

        return $offerQueryBuilder;
    }
}
