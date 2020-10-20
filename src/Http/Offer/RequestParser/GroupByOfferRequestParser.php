<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use InvalidArgumentException;

final class GroupByOfferRequestParser implements OfferRequestParserInterface
{
    private const SUPPORTED_GROUP_FIELDS = [
        'productionId',
    ];

    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $groupField = $request->getQueryParam('groupBy', null);
        if ($groupField === null) {
            return $offerQueryBuilder;
        }

        if (!in_array($groupField, self::SUPPORTED_GROUP_FIELDS, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown groupBy field "%s", supported fields: %s',
                    $groupField,
                    implode(', ', self::SUPPORTED_GROUP_FIELDS)
                )
            );
        }

        switch ($groupField) {
            case 'productionId':
                $offerQueryBuilder = $offerQueryBuilder->withGroupByProductionId();
                break;

            default:
                break;
        }

        return $offerQueryBuilder;
    }
}
