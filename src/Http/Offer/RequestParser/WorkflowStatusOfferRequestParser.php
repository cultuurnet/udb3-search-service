<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\Offer\WorkflowStatus;

final class WorkflowStatusOfferRequestParser implements OfferRequestParserInterface
{
    private const PARAMETER = 'workflowStatus';
    private const DEFAULT = 'APPROVED,READY_FOR_VALIDATION';

    public function parse(
        ApiRequestInterface $request,
        OfferQueryBuilderInterface $offerQueryBuilder
    ): OfferQueryBuilderInterface {
        $parameterBagReader = $request->getQueryParameterBag();

        $workflowStatuses = $parameterBagReader->getExplodedStringFromParameter(
            self::PARAMETER,
            self::DEFAULT,
            fn ($workflowStatus): WorkflowStatus => new WorkflowStatus($workflowStatus)
        );

        return $offerQueryBuilder->withWorkflowStatusFilter(...$workflowStatuses);
    }
}
