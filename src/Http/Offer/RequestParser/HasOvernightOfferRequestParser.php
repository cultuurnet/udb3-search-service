<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class HasOvernightOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $hasOvernight = $parameterBagReader->getBooleanFromParameter('hasOvernight');

        if (!is_null($hasOvernight)) {
            $offerQueryBuilder = $offerQueryBuilder->withHasOvernightFilter($hasOvernight);
        }

        return $offerQueryBuilder;
    }
}
