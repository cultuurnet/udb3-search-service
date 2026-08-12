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

        // Comma-separated only (dayOfWeek=friday,saturday), consistent with attendanceMode. The array
        // syntax (dayOfWeek[]=friday) is intentionally not supported: getExplodedStringFromParameter
        // rejects a multi-valued parameter with a clear "can only have a single value" error.
        $dayOfWeeks = $parameterBagReader->getExplodedStringFromParameter(
            'dayOfWeek',
            null,
            static fn (string $dayOfWeek): DayOfWeek => DayOfWeek::fromString($dayOfWeek)
        );

        if (!empty($dayOfWeeks)) {
            $offerQueryBuilder = $offerQueryBuilder->withDayOfWeekFilter(...$dayOfWeeks);
        }

        return $offerQueryBuilder;
    }
}
