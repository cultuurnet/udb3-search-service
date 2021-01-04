<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\Offer\CalendarType;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use ValueObjects\Number\Natural;

class CalendarOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $offerQueryBuilder = $this->parseCalendarType($parameterBagReader, $offerQueryBuilder);

        $dateFrom = $parameterBagReader->getDateTimeFromParameter('dateFrom');
        $dateTo = $parameterBagReader->getDateTimeFromParameter('dateTo');
        if ($dateFrom || $dateTo) {
            $offerQueryBuilder = $offerQueryBuilder->withDateRangeFilter($dateFrom, $dateTo);
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
