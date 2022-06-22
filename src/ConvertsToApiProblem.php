<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Crell\ApiProblem\ApiProblem;

interface ConvertsToApiProblem
{
    public function convertToApiProblem(): ApiProblem;
}
