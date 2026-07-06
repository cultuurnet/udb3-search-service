<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class BirthdateRangeOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $from = $parameterBagReader->getDateFromParameter('birthdateRangeFrom');
        $to = $parameterBagReader->getDateFromParameter('birthdateRangeTo');

        if ($from === null && $to === null) {
            return $offerQueryBuilder;
        }

        if ($from === null) {
            throw new MissingParameter(
                'Required "birthdateRangeFrom" parameter missing when searching by "birthdateRangeTo".'
            );
        }

        if ($to === null) {
            throw new MissingParameter(
                'Required "birthdateRangeTo" parameter missing when searching by "birthdateRangeFrom".'
            );
        }

        return $offerQueryBuilder->withBirthdateRangeFilter(
            new BirthdateRange(
                $from,
                $to
            )
        );
    }
}
