<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use DateTimeImmutable;
use InvalidArgumentException;

final class AvailabilityOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(ApiRequestInterface $request, OfferQueryBuilderInterface $offerQueryBuilder): OfferQueryBuilderInterface
    {
        $parameterBagReader = $request->getQueryParameterBag();

        $default = DateTimeImmutable::createFromFormat('U', (string) $request->getServerParam('REQUEST_TIME', 0));
        if (!$default) {
            throw new InvalidArgumentException('Invalid timestamp provided');
        }
        $default = $default->format(DATE_ATOM);

        $availableFrom = $parameterBagReader->getDateTimeFromParameter('availableFrom', $default);
        $availableTo = $parameterBagReader->getDateTimeFromParameter('availableTo', $default);

        // The defaults can still be overwritten with availableFrom=* and/or availableTo=* and/or
        // disableDefaultFilters=true, so only apply the filter if there is actually an availableFrom or availableTo to
        // filter on.
        if ($availableFrom || $availableTo) {
            $offerQueryBuilder = $offerQueryBuilder->withAvailableRangeFilter($availableFrom, $availableTo);
        }

        return $offerQueryBuilder;
    }
}
