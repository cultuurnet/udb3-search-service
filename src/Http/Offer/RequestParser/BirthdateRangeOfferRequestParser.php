<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;

final class BirthdateRangeOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $fromDates = $parameterBagReader->getExplodedDateFromParameter('birthdateRangeFrom');
        $toDates = $parameterBagReader->getExplodedDateFromParameter('birthdateRangeTo');

        if (empty($fromDates) && empty($toDates)) {
            return $offerQueryBuilder;
        }

        if (empty($fromDates)) {
            throw new MissingParameter(
                'Required "birthdateRangeFrom" parameter missing when searching by "birthdateRangeTo".'
            );
        }

        if (empty($toDates)) {
            throw new MissingParameter(
                'Required "birthdateRangeTo" parameter missing when searching by "birthdateRangeFrom".'
            );
        }

        if (count($fromDates) !== count($toDates)) {
            throw new UnsupportedParameterValue(
                'The number of "birthdateRangeFrom" values should match the number of "birthdateRangeTo" values.'
            );
        }

        $now = new Chronos();
        $ranges = array_map(
            static fn (DateTimeImmutable $from, DateTimeImmutable $to): BirthdateRange => new BirthdateRange($from, $to, $now),
            $fromDates,
            $toDates
        );

        return $offerQueryBuilder->withBirthdateRangeFilter(...$ranges);
    }
}
