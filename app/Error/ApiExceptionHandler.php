<?php declare(strict_types=1);


namespace CultuurNet\UDB3\SearchService\Error;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\Http\ResponseFactory;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Whoops\Handler\Handler;
use Zend\HttpHandlerRunner\Emitter\EmitterInterface;

class ApiExceptionHandler extends Handler
{
    private const HTTP_BAD_REQUEST = 400;

    /**
     * @var EmitterInterface
     */
    private $emitter;


    /**
     * ApiExceptionHandler constructor.
     * @param EmitterInterface $emitter
     */
    public function __construct(EmitterInterface $emitter)
    {
        $this->emitter = $emitter;
    }

    public function handle()
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

    private function createNewApiProblem(\Exception $e)
    {
        $problem = new ApiProblem($e->getMessage());
        $problem->setStatus($e->getCode() ?: self::HTTP_BAD_REQUEST);
        return $problem;
    }
}
