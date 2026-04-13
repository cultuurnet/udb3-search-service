<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Middleware\ApiProblems;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;

final class BadRequest extends ApiProblem
{
    public function __construct(string $detail)
    {
        parent::__construct('Bad Request', 'https://api.publiq.be/probs/body/bad-request');
        $this->setStatus(StatusCodeInterface::STATUS_BAD_REQUEST);
        $this->setDetail($detail);
    }

    public function toResponse(): ResponseInterface
    {
        return ResponseFactory::apiProblem(
            $this->asArray(),
            $this->getStatus()
        );
    }
}
