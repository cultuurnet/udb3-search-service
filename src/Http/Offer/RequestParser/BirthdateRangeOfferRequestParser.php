<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use Cake\Chronos\Chronos;
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
        $parameterBagReader = $request->getQueryParameterBag();

        $from = $parameterBagReader->getStringFromParameter('birthdateRangeFrom');
        $to = $parameterBagReader->getStringFromParameter('birthdateRangeTo');

        if ($from === null && $to === null) {
            return $offerQueryBuilder;
        }

        if ($from === null || $to === null) {
            throw new UnsupportedParameterValue(
                'birthdateRangeFrom and birthdateRangeTo should be used together'
            );
        }

        return $offerQueryBuilder->withBirthdateRangeFilter(
            new BirthdateRange(
                $this->parseDate('birthdateRangeFrom', $from),
                $this->parseDate('birthdateRangeTo', $to),
                new Chronos()
            )
        );
    }

    private function parseDate(string $parameterName, string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if (
            !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)
            || !$date
            || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        ) {
            throw new UnsupportedParameterValue(
                "{$parameterName} should be in the format YYYY-MM-DD, got \"{$value}\""
            );
        }

        return $date;
    }
}
