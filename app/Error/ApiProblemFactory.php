<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use Throwable;
use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\ConvertsToApiProblem;
use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchRequestFailed;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use Error;
use Fig\Http\Message\StatusCodeInterface;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;

final class ApiProblemFactory
{
    public static function createFromThrowable(Throwable $throwable): ApiProblem
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

        if ($throwable instanceof ElasticSearchRequestFailed) {
            // Without a reason in the response body (e.g. a proxy error page), the full message is the best detail left.
            $message = $throwable->getReason() ?? $throwable->getMessage();

            if (str_contains($message, 'Failed to parse query') ||
                str_contains($message, 'failed to create query') ||
                str_contains($message, 'unknown field [nested], parser not found')
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

        $problem = new ApiProblem('Internal Server Error');
        $problem->setStatus($throwable->getCode() ?: StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        $problem->setDetail($throwable->getMessage());
        return $problem;
    }
}
