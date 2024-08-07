<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\Age;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class AgeRangeOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $ageCallback = fn ($age): Age => new Age($age);

        $minAge = $parameterBagReader->getIntegerFromParameter('minAge', null, $ageCallback);
        $maxAge = $parameterBagReader->getIntegerFromParameter('maxAge', null, $ageCallback);
        if (!is_null($minAge) || !is_null($maxAge)) {
            $offerQueryBuilder = $offerQueryBuilder->withAgeRangeFilter($minAge, $maxAge);
        }

        $allAges = $parameterBagReader->getBooleanFromParameter('allAges');
        if (is_bool($allAges)) {
            $offerQueryBuilder = $offerQueryBuilder->withAllAgesFilter($allAges);
        }

        return $offerQueryBuilder;
    }
}
