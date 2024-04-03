<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\Parameters\ArrayParameterBagAdapter;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use CultuurNet\UDB3\Search\Organizer\WorkflowStatus;
use Psr\Http\Message\ServerRequestInterface;

final class WorkflowStatusOrganizerRequestParser implements OrganizerRequestParser
{
    private const PARAMETER = 'workflowStatus';
    private const DEFAULT = 'ACTIVE';

    public function parse(
        ServerRequestInterface $request,
        OrganizerQueryBuilderInterface $organizerQueryBuilder
    ): OrganizerQueryBuilderInterface {
        $parameterBagReader = new ArrayParameterBagAdapter($request->getQueryParams());

        $workflowStatuses = $parameterBagReader->getExplodedStringFromParameter(
            self::PARAMETER,
            self::DEFAULT,
            fn ($workflowStatus): WorkflowStatus => new WorkflowStatus($workflowStatus)
        );

        return $organizerQueryBuilder->withWorkflowStatusFilter(...$workflowStatuses);
    }
}
