<?php

namespace CultuurNet\UDB3\SearchService\Error;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use Elasticsearch\Common\Exceptions\ElasticsearchException;
use Silex\Application;
use Silex\ServiceProviderInterface;

class HttpErrorHandlerProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app->error(
            function (\Exception $e) use ($app) {
                if ($e instanceof ElasticsearchException) {
                    $errorData = json_decode($e->getMessage(), true);
                    $message = $errorData['error']['root_cause'][0]['reason'];
                    $jsonSerializableException = new \Exception($message);
                } else {
                    $jsonSerializableException = $e;
                }

                $problem = $this->createNewApiProblem($app, $jsonSerializableException);
                return new ApiProblemJsonResponse($problem);
            }
        );
    }

    /**
     * @param Application $app
     * @param \Exception $e
     * @return ApiProblem
     */
    protected function createNewApiProblem(Application $app, \Exception $e)
    {
        $problem = new ApiProblem($e->getMessage());
        $problem->setStatus($e->getCode() ? $e->getCode() : ApiProblemJsonResponse::HTTP_BAD_REQUEST);

        if (isset($app['api_problem.stacktrace']) && $app['api_problem.stacktrace']) {
            $problem['stacktrace'] = $e->getTrace();
        }

        return $problem;
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}
