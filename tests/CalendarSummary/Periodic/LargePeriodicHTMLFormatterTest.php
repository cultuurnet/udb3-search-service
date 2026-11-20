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
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LargePeriodicHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var LargePeriodicHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        // Monday 10 August 2026, so the adjusted days in November 2026 are upcoming.
        CalendarSummaryTester::setTestNow(2026, 8, 10);
        $this->formatter = new LargePeriodicHTMLFormatter(new Translator('nl_NL'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    public function testFormatAPeriodWithSingleTimeBlocks(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $place = $place->withOpeningHours(
            [
                new OpeningHour(
                    ['monday','tuesday', 'wednesday'],
                    '00:00',
                    '17:00'
                ),
                new OpeningHour(
                    ['friday', 'saturday'],
                    '10:00',
                    '18:00'
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-single-time-blocks'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAPeriodWithSingleTimeBlocksInFrench(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $place = $place->withOpeningHours(
            [
                new OpeningHour(
                    ['monday'],
                    '00:00',
                    '17:00'
                ),
                new OpeningHour(
                    ['friday'],
                    '10:00',
                    '18:00'
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-single-time-blocks-in-french'),
            (new LargePeriodicHTMLFormatter(new Translator('fr')))->format($place)
        );
    }

    public function testFormatAPeriodWithSingleTimeBlocksWithUnavailableStatus(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $event = $event->withOpeningHours(
            [
                new OpeningHour(
                    ['monday','tuesday', 'wednesday'],
                    '00:00',
                    '17:00'
                ),
                new OpeningHour(
                    ['friday', 'saturday'],
                    '10:00',
                    '18:00'
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-single-time-blocks-with-unavailable-status'),
            $this->formatter->format($event)
        );
    }

    public function testFormatAPeriodWithSplitTimeBlocks(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $place = $place->withOpeningHours(
            [
                new OpeningHour(
                    ['monday','tuesday', 'wednesday'],
                    '09:00',
                    '13:00'
                ),
                new OpeningHour(
                    ['monday','tuesday', 'wednesday'],
                    '17:00',
                    '20:00'
                ),
                new OpeningHour(
                    ['friday', 'saturday'],
                    '10:00',
                    '15:00'
                ),
                new OpeningHour(
                    ['friday', 'saturday'],
                    '18:00',
                    '21:00'
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-split-time-blocks'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAPeriodWithComplexTimeBlocks(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $place = $place->withOpeningHours(
            [
                new OpeningHour(
                    ['monday','tuesday'],
                    '09:30',
                    '13:45'
                ),
                new OpeningHour(
                    ['monday'],
                    '17:00',
                    '20:00'
                ),
                new OpeningHour(
                    ['tuesday'],
                    '00:00',
                    '20:00'
                ),
                new OpeningHour(
                    ['tuesday'],
                    '00:01',
                    '00:59'
                ),
                new OpeningHour(
                    ['friday', 'saturday'],
                    '10:00',
                    '15:00'
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-complex-time-blocks'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAPeriodWithoutTimeBlocks(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            $this->expectedHtml('period-without-time-blocks'),
            $this->formatter->format($place)
        );
    }

    public function testItNestsTheChildcareInsideTheDayItBelongsTo(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '17:00')),
                new OpeningHour(['tuesday'], '09:00', '12:00', new Childcare('08:00', null)),
                new OpeningHour(['wednesday'], '09:00', '12:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-childcare'),
            $this->formatter->format($place)
        );
    }

    /**
     * The timespans of a day share one line, so the childcare of all of them is joined into
     * a single mention after the last one.
     */
    public function testItJoinsTheChildcareOfEveryTimespanOfADay(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
                new OpeningHour(['monday'], '14:00', '18:00', new Childcare('13:00', '19:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-childcare-that-differs-per-timespan'),
            $this->formatter->format($place)
        );
    }

    /**
     * Childcare that every day has in common is summarized in a single list item, which
     * already is a list item of its own and therefore is not nested any further.
     */
    public function testItSummarizesTheChildcareThatEveryDayHasInCommon(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '16:00', new Childcare('08:00', '18:00')),
                new OpeningHour(['tuesday'], '09:00', '16:00', new Childcare('08:00', '18:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('period-with-shared-childcare'),
            $this->formatter->format($place)
        );
    }

    /**
     * The large format does not list the adjusted days, so it only warns that they exist.
     */
    public function testItWarnsThatTheHoursCanDifferDuringTheAdjustedDays(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours([new OpeningHour(['monday'], '09:00', '16:00')])
            ->withAdjustedDays([$this->autumnHoliday()]);

        $this->assertEquals(
            $this->expectedHtml('period-with-adjusted-days-notice'),
            $this->formatter->format($place)
        );
    }

    public function testItDoesNotWarnAboutAdjustedDaysThatHavePassed(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours([new OpeningHour(['monday'], '09:00', '16:00')])
            ->withAdjustedDays(
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
            $this->expectedHtml('period-without-a-notice-for-passed-adjusted-days'),
            $this->formatter->format($place)
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
            new OpeningHours([new OpeningHour(['monday'], '10:00', '15:00')]),
            ['nl' => 'Herfstvakantie']
        );
    }
}
