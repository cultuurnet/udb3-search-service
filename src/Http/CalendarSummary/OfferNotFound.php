<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\CalendarSummary;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Search\ConvertsToApiProblem;
use RuntimeException;

final class OfferNotFound extends RuntimeException implements ConvertsToApiProblem
{
    public function __construct(string $offerType, string $offerId)
    {
        parent::__construct('No ' . rtrim($offerType, 's') . ' found with id ' . $offerId . '.');
    }

    public function convertToApiProblem(): ApiProblem
    {
        $problem = new ApiProblem('Not Found', 'https://api.publiq.be/probs/url/not-found');
        $problem->setStatus(404);
        $problem->setDetail($this->getMessage());
        return $problem;
    }
}
