<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\AttendanceMode;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class AttendanceModeOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $attendanceModes = $parameterBagReader->getExplodedStringFromParameter(
            'attendanceMode',
            null,
            function (string $attendanceMode) {
                try {
                    return new AttendanceMode($attendanceMode);
                } catch (UnsupportedParameterValue $e) {
                    throw new UnsupportedParameterValue('Unknown attendance mode value "' . $attendanceMode . '"');
                }
            }
        );

        if (!empty($attendanceModes)) {
            $offerQueryBuilder = $offerQueryBuilder->withAttendanceModeFilter(...$attendanceModes);
        }

        return $offerQueryBuilder;
    }
}
