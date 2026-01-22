<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\ApiKeysMatchedToClientIds;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\ConvertsToApiProblem;
use CultuurNet\UDB3\Search\Http\Authentication\ApiProblems\InvalidApiKey;
use Exception;

final class UnmatchedApiKey extends Exception implements ConvertsToApiProblem
{
    public function __construct(readonly string $apiKey)
    {
        parent::__construct($this->apiKey . ' could not be matched to a clientId.');
    }

    public function convertToApiProblem(): ApiProblem
    {
        return new InvalidApiKey($this->apiKey);
    }
}
