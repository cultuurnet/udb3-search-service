<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\Parameters\SymfonyParameterBagAdapter;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\Number\Natural;

class IsDuplicateOfferRequestParser implements OfferRequestParserInterface
{
    /**
     * @param Request $request
     * @param OfferQueryBuilderInterface $offerQueryBuilder
     * @return OfferQueryBuilderInterface
     */
    public function parse(Request $request, OfferQueryBuilderInterface $offerQueryBuilder)
    {
        $parameterBagReader = new SymfonyParameterBagAdapter($request->query);

        $isDuplicate = $parameterBagReader->getBooleanFromParameter('isDuplicate', 'false');

        if (!is_null($isDuplicate)) {
            $offerQueryBuilder = $offerQueryBuilder->withDuplicateFilter($isDuplicate);
        }

        return $offerQueryBuilder;
    }
}
