<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

interface SingleFormatterInterface
{
    public function format(Offer $offer): string;
}
