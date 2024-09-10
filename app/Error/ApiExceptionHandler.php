<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Whoops\Handler\Handler;

final class ApiExceptionHandler extends Handler
{
    private EmitterInterface $emitter;

    public function __construct(EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    public function handle(): ?int
    {
        $exception = $this->getInspector()->getException();
        $problem = ApiProblemFactory::createFromThrowable($exception);

        $this->emitter->emit(
            ResponseFactory::apiProblem(
                $problem->asArray(),
                $problem->getStatus()
            )
        );

        return Handler::QUIT;
    }
}
