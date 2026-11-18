<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Middleware;

use Closure;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

interface FormatterMiddleware
{
    public function format(Offer $offer, Closure $next): string;
}
