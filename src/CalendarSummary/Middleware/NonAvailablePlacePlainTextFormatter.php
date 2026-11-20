<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Middleware;

use Closure;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

final class NonAvailablePlacePlainTextFormatter implements FormatterMiddleware
{
    /**
     * @var Translator
     */
    private $translator;

    public function __construct(
        Translator $translator
    ) {
        $this->translator = $translator;
    }

    public function format(Offer $offer, Closure $next): string
    {
        if ($offer->isPlace()) {
            if ($offer->getStatus()->getType() === 'Unavailable') {
                return $this->translator->translate('permanently_closed');
            }

            if ($offer->getStatus()->getType() === 'TemporarilyUnavailable') {
                return $this->translator->translate('temporarily_closed');
            }
        }

        return $next($offer);
    }
}
