<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class RecurringOnDayOfWeekOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        // Comma-separated only (recurringOnDayOfWeek=friday,saturday), consistent with attendanceMode.
        // The array syntax (recurringOnDayOfWeek[]=friday) is intentionally not supported:
        // getExplodedStringFromParameter rejects a multi-valued parameter with a clear "can only have
        // a single value" error.
        $dayOfWeeks = $parameterBagReader->getExplodedStringFromParameter(
            'recurringOnDayOfWeek',
            null,
            static fn (string $dayOfWeek): DayOfWeek => DayOfWeek::fromString($dayOfWeek)
        );

        $recurringOnLocalTimeFrom = $parameterBagReader->getIntegerFromParameter('recurringOnLocalTimeFrom');
        $recurringOnLocalTimeTo = $parameterBagReader->getIntegerFromParameter('recurringOnLocalTimeTo');

        if ($recurringOnLocalTimeFrom === null && $recurringOnLocalTimeTo === null) {
            if (empty($dayOfWeeks)) {
                return $offerQueryBuilder;
            }

            return $offerQueryBuilder->withRecurringOnDayOfWeekFilter(...$dayOfWeeks);
        }

        // The hours live under a key per day of week, so without a day of week there is no field to
        // range over. Falling back to the union over all days would be the very thing these
        // parameters exist to avoid.
        if (empty($dayOfWeeks)) {
            throw new UnsupportedParameterValue(
                'The "recurringOnLocalTimeFrom" and "recurringOnLocalTimeTo" parameters require'
                . ' "recurringOnDayOfWeek".'
            );
        }

        // An open-ended range would match every hour on one side, which reads as a narrower search
        // than it is. Both bounds are cheap to supply, so require them.
        if ($recurringOnLocalTimeFrom === null || $recurringOnLocalTimeTo === null) {
            throw new UnsupportedParameterValue(
                'The "recurringOnLocalTimeFrom" and "recurringOnLocalTimeTo" parameters have to be'
                . ' used together.'
            );
        }

        return $offerQueryBuilder->withRecurringOnLocalTimeRangeFilter(
            $recurringOnLocalTimeFrom,
            $recurringOnLocalTimeTo,
            ...$dayOfWeeks
        );
    }
}
