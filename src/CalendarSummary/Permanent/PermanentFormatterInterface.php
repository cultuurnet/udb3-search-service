<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

interface PermanentFormatterInterface
{
    public function format(Offer $offer): string;
}
