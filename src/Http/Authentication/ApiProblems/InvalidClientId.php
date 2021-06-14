<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\ApiProblems;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

final class InvalidClientId extends ApiProblem
{
    public function __construct(string $clientId)
    {
        parent::__construct('Unauthorized', 'https://api.publiq.be/probs/auth/unauthorized');
        $this->setStatus(401);
        $this->setDetail('The provided client id ' . $clientId . ' is invalid');
    }

    public function toResponse(): ResponseInterface
    {
        return ResponseFactory::jsonLd(
            $this->asArray(),
            $this->getStatus()
        );
    }
}
