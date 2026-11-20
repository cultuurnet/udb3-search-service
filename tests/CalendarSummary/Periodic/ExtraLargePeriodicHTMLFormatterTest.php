<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use Carbon\CarbonImmutable;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarSummaryTester;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\AdjustedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\ClosedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ExtraLargePeriodicHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    protected ExtraLargePeriodicHTMLFormatter $formatter;

    protected function setUp(): void
    {
        // Monday 10 August 2026, so the adjusted days in November 2026 are upcoming.
        CalendarSummaryTester::setTestNow(2026, 8, 10);
        $this->formatter = new ExtraLargePeriodicHTMLFormatter(new Translator('nl_NL'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    public function testFormatAPeriodWithSingleTimeBlocks(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday', 'tuesday', 'wednesday'], '00:00', '17:00'),
                new OpeningHour(['friday', 'saturday'], '10:00', '18:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-single-time-blocks'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAPeriodWithoutTimeBlocks(): void
    {
        $this->assertEquals(
            $this->expectedHtml('period-without-time-blocks'),
            $this->formatter->format($this->availablePlace())
        );
    }

    public function testFormatAPeriodWithSingleTimeBlocksWithUnavailableStatus(): void
    {
        $event = (new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        ))->withOpeningHours([new OpeningHour(['monday'], '00:00', '17:00')]);

        $this->assertEquals(
            $this->expectedHtml('period-with-single-time-blocks-with-unavailable-status'),
            $this->formatter->format($event)
        );
    }

    public function testFormatAPeriodWithAdjustedDays(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours([new OpeningHour(['monday'], '08:00', '17:00')])
            ->withAdjustedDays([$this->autumnHoliday()]);

        $this->assertEquals(
            $this->expectedHtml('period-with-adjusted-days'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAPeriodWithAdjustedDaysButWithoutTimeBlocks(): void
    {
        $place = $this->availablePlace()->withAdjustedDays([$this->autumnHoliday()]);

        $this->assertEquals(
            $this->expectedHtml('period-with-adjusted-days-but-without-time-blocks'),
            $this->formatter->format($place)
        );
    }

    public function testItSkipsAdjustedDaysThatHavePassed(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-08-03'),
                    new DateTimeImmutable('2026-08-09'),
                    new OpeningHours(),
                    ['nl' => 'Voorbij']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('it-skips-adjusted-days-that-have-passed'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAPeriodWithClosedDays(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours([new OpeningHour(['monday'], '08:00', '17:00')])
            ->withClosedDays(
                [
                    new ClosedDay(
                        new DateTimeImmutable('2026-12-24'),
                        new DateTimeImmutable('2027-01-03'),
                        ['nl' => 'Kerstvakantie']
                    ),
                ]
            );

        $this->assertEquals(
            $this->expectedHtml('period-with-closed-days'),
            $this->formatter->format($place)
        );
    }

    public function testItSkipsClosedDaysThatHavePassed(): void
    {
        $place = $this->availablePlace()->withClosedDays(
            [
                new ClosedDay(
                    new DateTimeImmutable('2026-08-03'),
                    new DateTimeImmutable('2026-08-09'),
                    ['nl' => 'Voorbij']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('it-skips-closed-days-that-have-passed'),
            $this->formatter->format($place)
        );
    }

    public function testFormatASharedChildcareAsASingleListItem(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday', 'tuesday'], '09:00', '16:00', new Childcare('08:00', '18:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('shared-childcare-as-a-single-list-item'),
            $this->formatter->format($place)
        );
    }

    public function testFormatADifferingChildcareOnTheDayItself(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
                new OpeningHour(['tuesday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('differing-childcare-on-the-day-itself'),
            $this->formatter->format($place)
        );
    }

    public function testFormatChildcareOnTheDayItselfWhenNotEveryDayHasIt(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
                new OpeningHour(['tuesday'], '09:00', '16:00'),
            ]
        );

        $summary = $this->formatter->format($place);

        $this->assertStringContainsString('<span class="cf-childcare">(Opvang van 8:00 tot 17:00)</span>', $summary);
        $this->assertStringNotContainsString('cf-childcare">Elke dag', $summary);
    }

    public function testFormatTheChildcareOfADayWithMultipleTimespansOnlyOnce(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '12:00', new Childcare('08:00', '18:00')),
                new OpeningHour(['monday'], '13:00', '17:00', new Childcare('08:00', '18:00')),
                new OpeningHour(['tuesday'], '09:00', '16:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('childcare-of-a-day-with-multiple-timespans-only-once'),
            $this->formatter->format($place)
        );
    }

    public function testFormatADayWithADifferentChildcarePerTimespan(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
                new OpeningHour(['monday'], '13:00', '17:00', new Childcare('12:00', '18:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('a-day-with-a-different-childcare-per-timespan'),
            $this->formatter->format($place)
        );
    }

    public function testFormatASharedChildcareWithoutAnEnd(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday', 'tuesday'], '10:00', '16:00', new Childcare('09:00', null)),
            ]
        );

        $this->assertStringContainsString(
            '<li class="cf-childcare">Elke dag vooropvang vanaf 9:00</li>',
            $this->formatter->format($place)
        );
    }

    public function testFormatASharedChildcareWithoutAStart(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday', 'tuesday'], '10:00', '16:00', new Childcare(null, '18:00')),
            ]
        );

        $this->assertStringContainsString(
            '<li class="cf-childcare">Elke dag naopvang tot 18:00</li>',
            $this->formatter->format($place)
        );
    }

    public function testFormatADifferingChildcareWithoutAnEndOrWithoutAStart(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '10:00', '16:00', new Childcare('09:00', null)),
                new OpeningHour(['tuesday'], '10:00', '16:00', new Childcare(null, '18:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('differing-childcare-without-an-end-or-without-a-start'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAChildcareWithoutAnEndInFrench(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '10:00', '16:00', new Childcare('09:00', null)),
            ]
        );

        $this->assertStringContainsString(
            '<li class="cf-childcare">Chaque jour garderie du matin dès 9:00</li>',
            (new ExtraLargePeriodicHTMLFormatter(new Translator('fr_BE')))->format($place)
        );
    }

    private function availablePlace(): Offer
    {
        return new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );
    }

    private function autumnHoliday(): AdjustedDay
    {
        return new AdjustedDay(
            new DateTimeImmutable('2026-11-02'),
            new DateTimeImmutable('2026-11-07'),
            new OpeningHours([new OpeningHour(['monday', 'tuesday', 'wednesday', 'thursday'], '09:00', '16:00')]),
            ['nl' => 'Herfstvakantie']
        );
    }

    /**
     * The most a summary can hold at once: a period, days that open more than once, a
     * childcare that differs per day and per timespan, an adjusted period with a childcare
     * of its own, and a closed period.
     */
    public function testFormatEverythingAtOnce(): void
    {
        $this->assertEquals(
            $this->expectedHtml('everything-at-once'),
            $this->formatter->format($this->placeWithEverything())
        );
    }

    /**
     * The summary that holds every wording at once is the one worth reading in all four
     * languages: it is where a word that is right in one phrase and wrong in another shows up.
     *
     * @dataProvider languages
     */
    public function testFormatEverythingAtOncePerLanguage(string $language, string $fixture): void
    {
        $this->assertEquals(
            $this->expectedHtml($fixture),
            (new ExtraLargePeriodicHTMLFormatter(new Translator($language)))->format(
                $this->placeWithEverything()
            )
        );
    }

    /**
     * @return array<string, string[]>
     */
    public function languages(): array
    {
        return [
            'french' => ['fr', 'everything-at-once-in-french'],
            'german' => ['de', 'everything-at-once-in-german'],
            'english' => ['en', 'everything-at-once-in-english'],
        ];
    }

    private function placeWithEverything(): Offer
    {
        return $this->availablePlace()
            ->withOpeningHours(
                [
                    new OpeningHour(['monday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
                    new OpeningHour(['monday'], '17:00', '19:00', new Childcare('16:00', '20:00')),
                    new OpeningHour(['tuesday', 'wednesday'], '10:00', '16:00', new Childcare('09:00', null)),
                    new OpeningHour(['saturday'], '10:00', '18:00'),
                ]
            )
            ->withAdjustedDays(
                [
                    new AdjustedDay(
                        new DateTimeImmutable('2026-11-02'),
                        new DateTimeImmutable('2026-11-07'),
                        new OpeningHours(
                            [new OpeningHour(['monday', 'tuesday'], '10:00', '15:00', new Childcare(null, '16:00'))]
                        ),
                        [
                            'nl' => 'Herfstvakantie',
                            'fr' => 'Vacances d\'automne',
                            'de' => 'Herbstferien',
                            'en' => 'Autumn holidays',
                        ]
                    ),
                ]
            )
            ->withClosedDays(
                [
                    new ClosedDay(
                        new DateTimeImmutable('2026-12-24'),
                        new DateTimeImmutable('2027-01-03'),
                        [
                            'nl' => 'Kerstvakantie',
                            'fr' => 'Vacances de Noël',
                            'de' => 'Weihnachtsferien',
                            'en' => 'Christmas holidays',
                        ]
                    ),
                ]
            );
    }
}
