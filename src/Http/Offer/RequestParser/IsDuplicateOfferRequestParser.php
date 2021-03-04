<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class IsDuplicateOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $isDuplicate = $parameterBagReader->getBooleanFromParameter('isDuplicate', 'false');

        if (!is_null($isDuplicate)) {
            $offerQueryBuilder = $offerQueryBuilder->withDuplicateFilter($isDuplicate);
        }

        return $offerQueryBuilder;
    }
}
