<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
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
        $ranges = $request->getQueryParameterBag()->getExplodedStringFromParameter(
            'birthdateRange',
            null,
            static function (string $range): BirthdateRange {
                $parts = explode('..', $range);
                if (count($parts) !== 2) {
                    throw new UnsupportedParameterValue(
                        "birthdateRange should be in the format YYYY-MM-DD..YYYY-MM-DD, got \"{$range}\""
                    );
                }

                $from = DateTimeImmutable::createFromFormat('!Y-m-d', $parts[0]);
                $to = DateTimeImmutable::createFromFormat('!Y-m-d', $parts[1]);

                if (!$from || !$to) {
                    throw new UnsupportedParameterValue(
                        "birthdateRange should be in the format YYYY-MM-DD..YYYY-MM-DD, got \"{$range}\""
                    );
                }

                return new BirthdateRange($from, $to);
            }
        );

        if (empty($ranges)) {
            return $offerQueryBuilder;
        }

        return $offerQueryBuilder->withBirthdateRangeFilter(...$ranges);
    }
}
