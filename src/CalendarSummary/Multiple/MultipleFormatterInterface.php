<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

interface MultipleFormatterInterface
{
    public function format(Offer $offer): string;
}
