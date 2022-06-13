<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Crell\ApiProblem\ApiProblem;
use InvalidArgumentException;

abstract class AbstractQueryParameterException extends InvalidArgumentException implements ConvertsToApiProblem
{
    public function convertToApiProblem(): ApiProblem
    {
        $problem = new ApiProblem(
            'Not Found',
            'https://api.publiq.be/probs/url/not-found'
        );
        $problem->setStatus(404);
        $problem->setDetail($this->message);
        return $problem;
    }
}
