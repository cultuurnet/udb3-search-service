<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\ConvertsToApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Error;
use Fig\Http\Message\StatusCodeInterface;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Whoops\Handler\Handler;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

final class ApiExceptionHandler extends Handler
{
    private EmitterInterface $emitter;

    /**
     * ApiExceptionHandler constructor.
     */
    public function __construct(EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    public function handle(): ?int
    {
        $exception = $this->getInspector()->getException();
        $problem = $this->createNewApiProblem($exception);

        $this->emitter->emit(
            ResponseFactory::apiProblem(
                $problem->asArray(),
                $problem->getStatus()
            )
        );

        return Handler::QUIT;
    }

    private function createNewApiProblem(\Throwable $throwable): ApiProblem
    {
        if ($throwable instanceof Error) {
            return (new ApiProblem('Internal server error'))
                ->setStatus(StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        }

        if ($throwable instanceof ConvertsToApiProblem) {
            return $throwable->convertToApiProblem();
        }

        if ($throwable instanceof NotFoundException) {
            $problem = new ApiProblem('Not Found', 'https://api.publiq.be/probs/url/not-found');
            $problem->setStatus(404);
            return $problem;
        }

        if ($throwable instanceof MethodNotAllowedException) {
            $problem = new ApiProblem('Method not allowed', 'https://api.publiq.be/probs/method/not-allowed');
            $problem->setStatus(405);
            return $problem;
        }

        if ($throwable instanceof ElasticsearchException) {
            $errorData = Json::decodeAssociatively($throwable->getMessage());
            $message = $errorData['error']['root_cause'][0]['reason'];

            if (strpos($message,'Failed to parse query') !== false ||
                strpos($message, 'failed to create query') !== false
            ) {
                $exception = new UnsupportedParameterValue(
                    'Could not parse query given "q" parameter as a valid Lucene query.'
                );
                return $exception->convertToApiProblem();
            }

            $problem = new ApiProblem('Internal Server Error');
            $problem->setStatus(500);
            $problem->setDetail('Elasticsearch error: ' . $message);
            return $problem;
        }

        $problem = new ApiProblem($throwable->getMessage());
        $problem->setStatus($throwable->getCode() ?: StatusCodeInterface::STATUS_BAD_REQUEST);
        return $problem;
    }
}
