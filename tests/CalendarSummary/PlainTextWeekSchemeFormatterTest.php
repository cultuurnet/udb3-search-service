<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use PHPUnit\Framework\TestCase;

final class PlainTextWeekSchemeFormatterTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        date_default_timezone_set('Europe/Brussels');
        $this->translator = new Translator('nl_NL');
    }

    public function testItRendersNothingWithoutOpeningHoursAsASingleLine(): void
    {
        $this->assertSame(
            '',
            PlainTextWeekSchemeFormatter::forOpeningHours(new OpeningHours(), $this->translator)
                ->asSingleLine()
                ->toString()
        );
    }

    public function testItRendersNothingWithoutOpeningHoursAsALinePerDay(): void
    {
        $this->assertSame(
            '',
            PlainTextWeekSchemeFormatter::forOpeningHours(new OpeningHours(), $this->translator)
                ->toString()
        );
    }

    public function testItStillMarksTheDaysWithoutOpeningHoursAsClosed(): void
    {
        $weekScheme = PlainTextWeekSchemeFormatter::forOpeningHours(
            new OpeningHours([new OpeningHour(['monday'], '10:00', '18:00')]),
            $this->translator
        )->toString();

        // Only an entirely empty week scheme is left out, a partially filled one
        // keeps reporting the days that are closed.
        $this->assertStringContainsString('Maandag van 10:00 tot 18:00', $weekScheme);
        $this->assertStringContainsString('Dinsdag gesloten', $weekScheme);
    }

    public function testItLeavesOutTheDaysWithoutOpeningHoursWhenAsked(): void
    {
        $weekScheme = PlainTextWeekSchemeFormatter::forOpeningHours(
            new OpeningHours([
                new OpeningHour(['wednesday'], '10:00', '18:00'),
                new OpeningHour(['monday'], '10:00', '18:00'),
            ]),
            $this->translator
        )->withoutClosedDays()->toString();

        $this->assertSame(
            'Maandag van 10:00 tot 18:00' . PHP_EOL . 'Woensdag van 10:00 tot 18:00',
            $weekScheme
        );
    }
}
