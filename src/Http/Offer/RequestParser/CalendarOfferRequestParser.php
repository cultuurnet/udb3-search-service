<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\Status;

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
                return Status::fromNative($status);
            }
        );
        $dateFrom = $parameterBagReader->getDateTimeFromParameter('dateFrom');
        $dateTo = $parameterBagReader->getDateTimeFromParameter('dateTo');

        $hasStatuses = !empty($statuses);
        $hasDates = !is_null($dateFrom) || !is_null($dateTo);

        if ($hasStatuses && $hasDates) {
            $offerQueryBuilder = $offerQueryBuilder->withStatusAwareDateRangeFilter($dateFrom, $dateTo, ...$statuses);
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
