<?php

namespace CultuurNet\UDB3\Search;

use Crell\ApiProblem\ApiProblem;

interface ConvertsToApiProblem
{
    public function convertToApiProblem(): ApiProblem;
}
