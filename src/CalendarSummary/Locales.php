<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

final class Locales
{
    // Shared by the embedded summaries and the calendar summary endpoint, so both render a language identically.
    public const BY_LANGUAGE = [
        'nl' => 'nl_BE',
        'fr' => 'fr_BE',
        'de' => 'de',
        'en' => 'en',
    ];
}
