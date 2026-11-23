<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use Cake\Chronos\Chronos;
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
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ExtraLargePermanentPlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    protected ExtraLargePermanentPlainTextFormatter $formatter;

    protected function setUp(): void
    {
        // Monday 10 August 2026, so the adjusted days in November 2026 are upcoming.
        Chronos::setTestNow(new Chronos('2026-08-10'));
        $this->formatter = new ExtraLargePermanentPlainTextFormatter(new Translator('nl_NL'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    public function testFormatASimplePermanent(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '14:00', '20:00'),
                new OpeningHour(['monday', 'tuesday', 'wednesday'], '00:01', '13:00'),
                new OpeningHour(['friday'], '09:00', '13:00'),
                new OpeningHour(['saturday', 'sunday'], '09:00', '19:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedText('simple-permanent') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    /**
     * The 'and' between two timespans of the same day is easily lost in French, where 'from' and
     * 'from_hour' do not translate to the same word.
     */
    public function testFormatMultipleTimespansOnTheSameDayInFrench(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '13:00'),
                new OpeningHour(['monday'], '17:00', '20:00'),
                new OpeningHour(['friday'], '10:00', '15:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedText('multiple-timespans-on-the-same-day-in-french') . PHP_EOL,
            (new ExtraLargePermanentPlainTextFormatter(new Translator('fr_BE')))->format($place)
        );
    }

    public function testFormatASharedChildcareAsASingleLine(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday', 'tuesday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedText('shared-childcare-as-a-single-line') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatADifferingChildcareOnTheDayItself(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
                new OpeningHour(['wednesday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedText('differing-childcare-on-the-day-itself') . PHP_EOL,
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

        $this->assertEquals(
            $this->expectedText('childcare-on-the-day-itself-when-not-every-day-has-it') . PHP_EOL,
            $this->formatter->format($place)
        );
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
            $this->expectedText('childcare-of-a-day-with-multiple-timespans-only-once') . PHP_EOL,
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
            $this->expectedText('a-day-with-a-different-childcare-per-timespan') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatAChildcareWithoutAnEndOrWithoutAStart(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', null)),
                new OpeningHour(['tuesday'], '09:00', '16:00', new Childcare(null, '17:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedText('childcare-without-an-end-or-without-a-start') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatASharedChildcareInFrench(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
            ]
        );

        $this->assertStringContainsString(
            PHP_EOL . ' (chaque jour garderie de 8:00 à 17:00)',
            (new ExtraLargePermanentPlainTextFormatter(new Translator('fr_BE')))->format($place)
        );
    }

    public function testFormatASharedChildcareInAnAdjustedDay(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-07'),
                    new OpeningHours([
                        new OpeningHour(['monday', 'tuesday'], '09:00', '12:00', new Childcare('08:00', '18:00')),
                        new OpeningHour(['thursday'], '13:00', '17:00', new Childcare('08:00', '18:00')),
                    ]),
                    ['nl' => 'Herfstvakantie']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('shared-childcare-in-an-adjusted-day') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatADifferingChildcareInAnAdjustedDay(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-07'),
                    new OpeningHours([
                        new OpeningHour(['monday', 'tuesday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
                        new OpeningHour(['thursday'], '13:00', '17:00', new Childcare('12:00', '18:00')),
                    ])
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('differing-childcare-in-an-adjusted-day') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatAnUnavailablePermanent(): void
    {
        $this->assertEquals(
            'Geannuleerd',
            $this->formatter->format($this->unavailableEvent())
        );
    }

    public function testFormatATemporarilyUnavailablePermanent(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $this->assertEquals(
            'Uitgesteld',
            $this->formatter->format($event)
        );
    }

    public function testFormatPermanentWithoutOpeningHours(): void
    {
        $this->assertEquals(
            'Alle dagen open' . PHP_EOL,
            $this->formatter->format($this->availablePlace())
        );
    }

    public function testFormatAdjustedDaysAfterTheWeekScheme(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours([new OpeningHour(['monday'], '09:00', '13:00')])
            ->withAdjustedDays([$this->autumnHoliday()]);

        $this->assertEquals(
            $this->expectedText('adjusted-days-after-the-week-scheme') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatAdjustedDaysForASinglePeriod(): void
    {
        $place = $this->availablePlace()->withAdjustedDays([$this->autumnHoliday()]);

        $this->assertEquals(
            $this->expectedText('adjusted-days-for-a-single-period') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatAdjustedDaysForASinglePeriodInFrench(): void
    {
        $place = $this->availablePlace()->withAdjustedDays([$this->autumnHoliday()]);

        $this->assertEquals(
            $this->expectedText('adjusted-days-for-a-single-period-in-french') . PHP_EOL,
            (new ExtraLargePermanentPlainTextFormatter(new Translator('fr_BE')))->format($place)
        );
    }

    public function testFormatAdjustedDaysForASingleDay(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-02'),
                    new OpeningHours([new OpeningHour(['monday'], '10:00', '12:00')]),
                    ['nl' => 'Feestdag']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('adjusted-days-for-a-single-day') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatAdjustedDaysWithMultipleOpeningHours(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-07'),
                    new OpeningHours([
                        new OpeningHour(['monday', 'tuesday'], '09:00', '12:00'),
                        new OpeningHour(['thursday', 'friday', 'saturday'], '13:00', '17:00'),
                    ]),
                    ['nl' => 'Herfstvakantie']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('adjusted-days-with-multiple-opening-hours') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatAdjustedDaysWithNonConsecutiveDays(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-07'),
                    new OpeningHours([new OpeningHour(['wednesday', 'monday'], '09:00', '16:00')])
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('adjusted-days-with-non-consecutive-days') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatAdjustedDaysWithoutOpeningHours(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-12-24'),
                    new DateTimeImmutable('2027-01-03'),
                    new OpeningHours(),
                    ['nl' => 'Kerstvakantie']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('adjusted-days-without-opening-hours') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItDoesNotRenderTheDescriptionWhenTranslationIsUnavailable(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-02'),
                    new OpeningHours(),
                    ['fr' => 'Congé d\'automne']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('it-does-not-render-the-description-when-translation-is-unavailable') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItRendersMultipleAdjustedDays(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-02'),
                    new OpeningHours(),
                    ['nl' => 'Herfstvakantie']
                ),
                new AdjustedDay(
                    new DateTimeImmutable('2026-12-24'),
                    new DateTimeImmutable('2026-12-24'),
                    new OpeningHours(),
                    ['nl' => 'Kerstavond']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('it-renders-multiple-adjusted-days') . PHP_EOL,
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
            'Alle dagen open' . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItRendersAdjustedDaysThatEndToday(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-08-03'),
                    new DateTimeImmutable('2026-08-10'),
                    new OpeningHours(),
                    ['nl' => 'Loopt vandaag af']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('it-renders-adjusted-days-that-end-today') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testFormatClosedDays(): void
    {
        $place = $this->availablePlace()->withClosedDays([$this->christmasHoliday()]);

        $this->assertEquals(
            $this->expectedText('closed-days') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItListsTheDaysWithoutOpeningHoursAsClosedEveryWeek(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours(
                [
                    new OpeningHour(
                        ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                        '13:00',
                        '17:00',
                        new Childcare('13:00', '17:30')
                    ),
                ]
            )
            ->withClosedDays([$this->christmasHoliday()]);

        $this->assertEquals(
            $this->expectedText('days-without-opening-hours-closed-every-week') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItListsTheDaysWithoutOpeningHoursAsClosedEveryWeekWithoutClosedPeriods(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '09:00', '17:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedText('days-without-opening-hours-closed-every-week-without-closed-periods') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItListsTheDaysWithoutOpeningHoursAsClosedEveryWeekWhenTheClosedPeriodsHavePassed(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours(
                [
                    new OpeningHour(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], '09:00', '17:00'),
                ]
            )
            ->withClosedDays(
                [
                    new ClosedDay(
                        new DateTimeImmutable('2026-08-03'),
                        new DateTimeImmutable('2026-08-09'),
                        ['nl' => 'Voorbij']
                    ),
                ]
            );

        $this->assertEquals(
            $this->expectedText('days-without-opening-hours-closed-every-week-without-closed-periods') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItListsTheDaysWithoutOpeningHoursAsClosedEveryWeekInFrench(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'], '13:00', '17:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedText('days-without-opening-hours-closed-every-week-in-french') . PHP_EOL,
            (new ExtraLargePermanentPlainTextFormatter(new Translator('fr_BE')))->format($place)
        );
    }

    public function testFormatClosedDaysInFrench(): void
    {
        $place = $this->availablePlace()->withClosedDays([$this->christmasHoliday()]);

        $this->assertEquals(
            $this->expectedText('closed-days-in-french') . PHP_EOL,
            (new ExtraLargePermanentPlainTextFormatter(new Translator('fr_BE')))->format($place)
        );
    }

    public function testFormatClosedDaysForASingleDay(): void
    {
        $place = $this->availablePlace()->withClosedDays(
            [
                new ClosedDay(
                    new DateTimeImmutable('2026-12-25'),
                    new DateTimeImmutable('2026-12-25'),
                    ['nl' => 'Kerstmis']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('closed-days-for-a-single-day') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItRendersMultipleClosedDays(): void
    {
        $place = $this->availablePlace()->withClosedDays(
            [
                $this->christmasHoliday(),
                new ClosedDay(
                    new DateTimeImmutable('2027-04-05'),
                    new DateTimeImmutable('2027-04-18'),
                    ['nl' => 'Paasvakantie']
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('it-renders-multiple-closed-days') . PHP_EOL,
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
            'Alle dagen open' . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItRendersTheClosedDaysAfterTheAdjustedDays(): void
    {
        $place = $this->availablePlace()
            ->withAdjustedDays([$this->autumnHoliday()])
            ->withClosedDays([$this->christmasHoliday()]);

        $this->assertEquals(
            $this->expectedText('it-renders-the-closed-days-after-the-adjusted-days') . PHP_EOL,
            $this->formatter->format($place)
        );
    }

    public function testItDoesNotRenderClosedDaysForAnUnavailablePermanent(): void
    {
        $event = $this->unavailableEvent()->withClosedDays([$this->christmasHoliday()]);

        $this->assertEquals(
            'Geannuleerd',
            $this->formatter->format($event)
        );
    }

    public function testItDoesNotRenderAdjustedDaysForAnUnavailablePermanent(): void
    {
        $event = $this->unavailableEvent()->withAdjustedDays([$this->autumnHoliday()]);

        $this->assertEquals(
            'Geannuleerd',
            $this->formatter->format($event)
        );
    }

    private function availablePlace(): Offer
    {
        return new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );
    }

    private function unavailableEvent(): Offer
    {
        return new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
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

    private function christmasHoliday(): ClosedDay
    {
        return new ClosedDay(
            new DateTimeImmutable('2026-12-24'),
            new DateTimeImmutable('2027-01-03'),
            ['nl' => 'Kerstvakantie']
        );
    }
    /**
     * A day that opens more than once keeps its timespans on one line, and mentions the
     * childcare of all of them together after the last one.
     */
    public function testFormatAnAdjustedDayThatOpensTwiceOnTheSameDay(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-20'),
                    new DateTimeImmutable('2026-11-21'),
                    new OpeningHours([
                        new OpeningHour(['friday'], '08:00', '12:00', new Childcare('07:00', '13:00')),
                        new OpeningHour(['friday'], '17:00', '19:00', new Childcare('16:00', '20:00')),
                        new OpeningHour(['saturday'], '10:00', '18:00', new Childcare(null, '19:00')),
                    ])
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('adjusted-days-with-a-day-that-opens-twice') . PHP_EOL,
            $this->formatter->format($place)
        );
    }
}
