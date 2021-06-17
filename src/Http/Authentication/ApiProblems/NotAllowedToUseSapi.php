<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\ApiProblems;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

final class NotAllowedToUseSapi extends ApiProblem
{
    public function __construct(string $clientId)
    {
        parent::__construct('Forbidden', 'https://api.publiq.be/probs/auth/forbidden');
        $this->setStatus(403);
        $this->setDetail('The provided client id ' . $clientId . ' has missing sapi3 scope');
    }

    public function toResponse(): ResponseInterface
    {
        return ResponseFactory::jsonLd(
            $this->asArray(),
            $this->getStatus()
        );
    }
}
