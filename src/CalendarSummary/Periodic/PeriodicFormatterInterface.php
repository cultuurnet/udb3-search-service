<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

interface PeriodicFormatterInterface
{
    public function format(Offer $offer): string;
}
