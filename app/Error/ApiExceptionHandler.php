<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Error;
use Fig\Http\Message\StatusCodeInterface;
use Whoops\Handler\Handler;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

final class ApiExceptionHandler extends Handler
{
    /**
     * @var EmitterInterface
     */
    private $emitter;

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
        if ($exception instanceof ElasticsearchException) {
            $errorData = json_decode($exception->getMessage(), true);
            $message = $errorData['error']['root_cause'][0]['reason'];
            $jsonSerializableException = new \Exception($message);
        } else {
            $jsonSerializableException = $exception;
        }

        $problem = $this->createNewApiProblem($jsonSerializableException);

        $this->emitter->emit(
            ResponseFactory::jsonLd(
                $problem->asArray()
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

        $problem = new ApiProblem($throwable->getMessage());
        $problem->setStatus($throwable->getCode() ?: StatusCodeInterface::STATUS_BAD_REQUEST);
        return $problem;
    }
}
