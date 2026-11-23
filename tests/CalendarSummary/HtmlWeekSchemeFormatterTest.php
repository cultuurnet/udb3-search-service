<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use PHPUnit\Framework\TestCase;

final class HtmlWeekSchemeFormatterTest extends TestCase
{
    use HtmlFixture;

    private Translator $translator;

    protected function setUp(): void
    {
        date_default_timezone_set('Europe/Brussels');
        $this->translator = new Translator('nl_NL');
    }

    public function testItRendersNothingWithoutOpeningHours(): void
    {
        $this->assertSame(
            '',
            HtmlWeekSchemeFormatter::forOpeningHours(new OpeningHours(), $this->translator)
                ->toString()
        );
    }

    public function testItRendersNoHeadingOrClosedDaysWithoutOpeningHours(): void
    {
        $this->assertSame(
            '',
            HtmlWeekSchemeFormatter::forOpeningHours(new OpeningHours(), $this->translator)
                ->withHeading()
                ->withEveryDayOfTheWeek()
                ->toString()
        );
    }

    /**
     * A childcare that every day shares is summarized in a list item of its own, one that
     * differs per day follows the day it belongs to. Without withChildcare() neither shows
     * up, so both of these render the very same week scheme.
     */
    public function testItRendersNoChildcareUnlessAsked(): void
    {
        $shared = new OpeningHours([
            new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
            new OpeningHour(['tuesday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
        ]);

        $differing = new OpeningHours([
            new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
            new OpeningHour(['tuesday'], '09:00', '16:00'),
        ]);

        foreach ([$shared, $differing] as $openingHours) {
            $this->assertEquals(
                $this->expectedHtml('week-scheme-without-childcare'),
                HtmlWeekSchemeFormatter::forOpeningHours($openingHours, $this->translator)->toString()
            );
        }
    }

    public function testItStillMarksTheDaysWithoutOpeningHoursAsClosed(): void
    {
        $weekScheme = HtmlWeekSchemeFormatter::forOpeningHours(
            new OpeningHours([new OpeningHour(['monday'], '10:00', '18:00')]),
            $this->translator
        )->withEveryDayOfTheWeek()->toString();

        // Only an entirely empty week scheme is left out, a partially filled one
        // keeps reporting the days that are closed.
        $this->assertStringContainsString('Maandag', $weekScheme);
        $this->assertStringContainsString('cf-closed', $weekScheme);
    }

    public function testItListsOnlyTheOpenDaysInWeekOrder(): void
    {
        $weekScheme = HtmlWeekSchemeFormatter::forOpeningHours(
            new OpeningHours([
                new OpeningHour(['wednesday'], '10:00', '18:00'),
                new OpeningHour(['monday'], '10:00', '18:00'),
            ]),
            $this->translator
        )->inWeekOrder()->toString();

        $this->assertLessThan(strpos($weekScheme, 'Woensdag'), strpos($weekScheme, 'Maandag'));
        $this->assertStringNotContainsString('Dinsdag', $weekScheme);
        $this->assertStringNotContainsString('cf-closed', $weekScheme);
    }
}
