<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;
use InvalidArgumentException;

final class BirthdateRangeOfferRequestParser implements OfferRequestParserInterface
{
    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $now = DateTimeImmutable::createFromFormat('U', (string) $request->getServerParam('REQUEST_TIME', 0));
        if (!$now) {
            throw new InvalidArgumentException('Invalid timestamp provided');
        }

        $ranges = $request->getQueryParameterBag()->getExplodedStringFromParameter(
            'birthdateRange',
            null,
            function (string $range) use ($now): BirthdateRange {
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

                return new BirthdateRange($from, $to, $now);
            }
        );

        if (empty($ranges)) {
            return $offerQueryBuilder;
        }

        return $offerQueryBuilder->withBirthdateRangeFilter(...$ranges);
    }
}
