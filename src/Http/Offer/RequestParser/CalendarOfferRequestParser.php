<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\Status;
use CultuurNet\UDB3\Search\Offer\SubEventQueryParameters;

final class CalendarOfferRequestParser implements OfferRequestParserInterface
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
            Status::fromString(...)
        );
        $bookingAvailability = $parameterBagReader->getStringFromParameter('bookingAvailability') ?: null;
        $hasChildcare = $parameterBagReader->getBooleanFromParameter('hasChildcare');
        $hasOvernightStay = $parameterBagReader->getBooleanFromParameter('hasOvernightStay');
        $dateFrom = $parameterBagReader->getDateTimeFromParameter('dateFrom');
        $dateTo = $parameterBagReader->getDateTimeFromParameter('dateTo');
        $localTimeFrom = $parameterBagReader->getIntegerFromParameter('localTimeFrom');
        $localTimeTo = $parameterBagReader->getIntegerFromParameter('localTimeTo');

        $hasStatuses = !empty($statuses);
        $hasBookingAvailability = !is_null($bookingAvailability);
        $hasChildcareFilter = !is_null($hasChildcare);
        $hasOvernightStayFilter = !is_null($hasOvernightStay);
        $hasDates = !is_null($dateFrom) || !is_null($dateTo);
        $hasLocalTimes =  !is_null($localTimeFrom) || !is_null($localTimeTo);

        $filterCount = count(array_filter([
            $hasDates,
            $hasLocalTimes,
            $hasStatuses,
            $hasBookingAvailability,
            $hasChildcareFilter,
            $hasOvernightStayFilter,
        ]));

        // A date or time combined with any other calendar filter has to match within one subEvent. Otherwise an event
        // with one subEvent on the right date and another one with the right status would match.
        if (($hasDates || $hasLocalTimes) && $filterCount > 1) {
            return $offerQueryBuilder->withSubEventFilter(
                (new SubEventQueryParameters())
                    ->withDateFrom($dateFrom)
                    ->withDateTo($dateTo)
                    ->withLocalTimeFrom($localTimeFrom)
                    ->withLocalTimeTo($localTimeTo)
                    ->withStatuses($statuses)
                    ->withBookingAvailability($bookingAvailability)
                    ->withHasChildcare($hasChildcare)
                    ->withHasOvernightStay($hasOvernightStay)
            );
        }

        // Every other combination is filtered on the properties that are aggregated over all subEvents.
        if ($hasDates) {
            $offerQueryBuilder = $offerQueryBuilder->withDateRangeFilter($dateFrom, $dateTo);
        }
        if ($hasLocalTimes) {
            $offerQueryBuilder = $offerQueryBuilder->withLocalTimeRangeFilter($localTimeFrom, $localTimeTo);
        }
        if ($hasStatuses) {
            $offerQueryBuilder = $offerQueryBuilder->withStatusFilter(...$statuses);
        }
        if ($hasBookingAvailability) {
            $offerQueryBuilder = $offerQueryBuilder->withBookingAvailabilityFilter($bookingAvailability);
        }
        if ($hasChildcareFilter) {
            $offerQueryBuilder = $offerQueryBuilder->withHasChildcareFilter($hasChildcare);
        }
        if ($hasOvernightStayFilter) {
            $offerQueryBuilder = $offerQueryBuilder->withHasOvernightStayFilter($hasOvernightStay);
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
            fn ($calendarType): CalendarType => new CalendarType($calendarType)
        );

        if (!empty($calendarTypes)) {
            $offerQueryBuilder = $offerQueryBuilder->withCalendarTypeFilter(...$calendarTypes);
        }

        return $offerQueryBuilder;
    }
}
