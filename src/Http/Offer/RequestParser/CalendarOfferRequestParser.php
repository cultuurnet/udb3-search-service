<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\Status;
use CultuurNet\UDB3\Search\Offer\SubEventQueryParameters;
use InvalidArgumentException;

class CalendarOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $offerQueryBuilder = $this->parseCalendarType($parameterBagReader, $offerQueryBuilder);

        $statuses = $parameterBagReader->getExplodedStringFromParameter(
            'status',
            null,
            function (string $status) {
                try {
                    return Status::fromNative($status);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException('Unknown status value "' . $status . '"');
                }
            }
        );
        $dateFrom = $parameterBagReader->getDateTimeFromParameter('dateFrom');
        $dateTo = $parameterBagReader->getDateTimeFromParameter('dateTo');
        $localTimeFrom = $parameterBagReader->getIntegerFromParameter('localTimeFrom');
        $localTimeTo = $parameterBagReader->getIntegerFromParameter('localTimeTo');

        $hasStatuses = !empty($statuses);
        $hasDates = !is_null($dateFrom) || !is_null($dateTo);
        $hasLocalTimes =  !is_null($localTimeFrom) || !is_null($localTimeTo);

        $hasMultipleFilters = ((int) $hasStatuses + (int) $hasDates + (int) $hasLocalTimes) > 1;

        // If the URL has parameters to filter on date AND status, filter by subEvent because otherwise we can get false
        // positives (for example an event with a subEvent that has the right date but the wrong status and also a
        // subEvent with the wrong date but right status -> matches if not filtering by subEvent)
        // On the other hand if the URL only filters by status but not by date, the filtering should happen on the top
        // level status because an event can have multiple statuses when filtering by subEvent.
        // For dateRange and localTimeRange it's just more performant to filter on the aggregated properties index on
        // the top level if they are not combined with status or each other.
        if ($hasMultipleFilters) {
            $offerQueryBuilder = $offerQueryBuilder->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withDateFrom($dateFrom)
                    ->withDateTo($dateTo)
                    ->withLocalTimeFrom($localTimeFrom)
                    ->withLocalTimeTo($localTimeTo)
                    ->withStatuses($statuses)
            );
        } elseif ($hasDates) {
            $offerQueryBuilder = $offerQueryBuilder->withDateRangeFilter($dateFrom, $dateTo);
        } elseif ($hasStatuses) {
            $offerQueryBuilder = $offerQueryBuilder->withStatusFilter(...$statuses);
        }

        return $offerQueryBuilder;
    }

    private function parseCalendarType(
        ParameterBagInterface $parameterBagReader,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $calendarTypes = $parameterBagReader->getExplodedStringFromParameter(
            'calendarType',
            null,
            function ($calendarType) {
                return new CalendarType($calendarType);
            }
        );

        if (!empty($calendarTypes)) {
            $offerQueryBuilder = $offerQueryBuilder->withCalendarTypeFilter(...$calendarTypes);
        }

        return $offerQueryBuilder;
    }
}
