<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

/**
 * Normalizes the whitespace of an assembled HTML summary: the elements are
 * separated by a single space and no two spaces follow each other.
 */
final class HtmlSummaryFormatter
{
    public static function format(string $calsum): string
    {
        $calsum = str_replace('><', '> <', $calsum);
        return str_replace('  ', ' ', $calsum);
    }
}
