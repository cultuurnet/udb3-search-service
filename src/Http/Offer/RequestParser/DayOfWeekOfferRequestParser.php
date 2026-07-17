<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;

final class DayOfWeekOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $dayOfWeeks = $parameterBagReader->getExplodedStringFromParameter(
            'dayOfWeek',
            null,
            fn (string $dayOfWeek): DayOfWeek => new DayOfWeek($dayOfWeek)
        );

        if (!empty($dayOfWeeks)) {
            $offerQueryBuilder = $offerQueryBuilder->withDayOfWeekFilter(...$dayOfWeeks);
        }

        return $offerQueryBuilder;
    }
}
