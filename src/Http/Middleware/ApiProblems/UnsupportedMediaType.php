<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Middleware\ApiProblems;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;

final class UnsupportedMediaType extends ApiProblem
{
    public function __construct()
    {
        parent::__construct('Unsupported Media Type', 'https://api.publiq.be/probs/body/unsupported-media-type');
        $this->setStatus(StatusCodeInterface::STATUS_UNSUPPORTED_MEDIA_TYPE);
        $this->setDetail('POST requests require Content-Type text/plain.');
    }

    public function toResponse(): ResponseInterface
    {
        return ResponseFactory::apiProblem(
            $this->asArray(),
            $this->getStatus()
        );
    }
}
