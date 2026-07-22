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

        // Support both the array syntax (dayOfWeek[]=friday&dayOfWeek[]=saturday) and the
        // comma-separated syntax (dayOfWeek=friday,saturday). getArrayFromParameter normalizes
        // a single scalar to a one-element array and keeps repeated values as an array, after
        // which each value is still exploded on commas so both forms are OR-combined.
        $dayOfWeeks = [];
        foreach ($parameterBagReader->getArrayFromParameter('dayOfWeek') as $rawValue) {
            foreach (explode(',', (string) $rawValue) as $value) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
                $dayOfWeeks[] = new DayOfWeek($value);
            }
        }

        if (!empty($dayOfWeeks)) {
            $offerQueryBuilder = $offerQueryBuilder->withDayOfWeekFilter(...$dayOfWeeks);
        }

        return $offerQueryBuilder;
    }
}
