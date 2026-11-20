<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

interface CalendarFormatterInterface
{
    public function format(Offer $offer, string $format): string;
}
