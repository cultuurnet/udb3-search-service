<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\AdjustedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LargePeriodicPlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var LargePeriodicPlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        // Monday 10 August 2026, so the adjusted days in November 2026 are upcoming.
        Chronos::setTestNow(new Chronos('2026-08-10'));
        $this->formatter = new LargePeriodicPlainTextFormatter(new Translator('nl_NL'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
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
                    '00:01',
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
            $this->expectedText('period-with-single-time-blocks'),
            $this->formatter->format($place)
        );
    }

    /**
     * Every language has its own wording for the period and its own way of writing the days
     * of the week: German and English keep their capital inside the single line that lists
     * the opening hours, French and Dutch lose it there.
     *
     * @dataProvider languages
     */
    public function testFormatAPeriodWithSingleTimeBlocksPerLanguage(string $language, string $fixture): void
    {
        $this->assertEquals(
            $this->expectedText($fixture),
            (new LargePeriodicPlainTextFormatter(new Translator($language)))->format(
                $this->periodicPlaceOpenOnMondayAndFriday()
            )
        );
    }

    /**
     * @return array<string, string[]>
     */
    public function languages(): array
    {
        return [
            'french' => ['fr', 'period-with-single-time-blocks-in-french'],
            'german' => ['de', 'period-with-single-time-blocks-in-german'],
            'english' => ['en', 'period-with-single-time-blocks-in-english'],
        ];
    }

    private function periodicPlaceOpenOnMondayAndFriday(): Offer
    {
        return (new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        ))->withOpeningHours(
            [
                new OpeningHour(['monday'], '00:01', '17:00'),
                new OpeningHour(['friday'], '10:00', '18:00'),
            ]
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
                    '00:01',
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
            $this->expectedText('period-with-single-time-blocks-with-unavailable-status'),
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
            $this->expectedText('period-with-split-time-blocks'),
            $this->formatter->format($place)
        );
    }

    /**
     * The 'and' between two timespans of the same day is easily lost in French, where 'from' and
     * 'from_hour' do not translate to the same word.
     */
    public function testFormatAPeriodWithSplitTimeBlocksInFrench(): void
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
                    '09:00',
                    '13:00'
                ),
                new OpeningHour(
                    ['monday'],
                    '17:00',
                    '20:00'
                ),
                new OpeningHour(
                    ['friday'],
                    '10:00',
                    '15:00'
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('period-with-split-time-blocks-in-french'),
            (new LargePeriodicPlainTextFormatter(new Translator('fr')))->format($place)
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
                    '18:00',
                    '20:00'
                ),
                new OpeningHour(
                    ['tuesday'],
                    '21:00',
                    '23:00'
                ),
                new OpeningHour(
                    ['friday', 'saturday'],
                    '10:00',
                    '15:00'
                ),
            ]
        );

        $this->assertEquals(
            $this->expectedText('period-with-complex-time-blocks'),
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
            'Van dinsdag 25 november 2025 tot en met zaterdag 30 november 2030',
            $this->formatter->format($place)
        );
    }

    public function testFormatAPeriodWithoutTimeBlocksWithStatusUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Van dinsdag 25 november 2025 tot en met zaterdag 30 november 2030 (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    /**
     * The days share a single line, so their childcare gets a line of its own and mentions
     * the day it applies to.
     */
    public function testItShowsTheChildcare(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
                new OpeningHour(['monday'], '14:00', '18:00', new Childcare('13:00', '19:00')),
                new OpeningHour(['tuesday'], '09:00', '16:00', new Childcare('08:00', null)),
                new OpeningHour(['wednesday'], '09:00', '16:00'),
            ]
        );

        $this->assertEquals(
            $this->expectedText('period-with-childcare'),
            $this->formatter->format($place)
        );
    }

    /**
     * The whole week scheme is a single string, so the availability lands after the last
     * childcare line rather than after the opening hours, and only the notice starts a
     * line of its own. The extra large format has always read that way.
     */
    public function testItShowsTheChildcareTheAvailabilityAndTheNoticeTogether(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours(
                [
                    new OpeningHour(['monday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
                    new OpeningHour(['monday'], '14:00', '18:00', new Childcare('13:00', '19:00')),
                    new OpeningHour(['tuesday'], '09:00', '16:00', new Childcare('08:00', null)),
                ]
            )
            ->withAdjustedDays([$this->autumnHoliday()])
            ->withAvailability(new Status('Unavailable', []), new BookingAvailability('Available'));

        $this->assertEquals(
            $this->expectedText('period-with-childcare-and-adjusted-days-with-unavailable-status'),
            $this->formatter->format($place)
        );
    }

    /**
     * The large format does not list the adjusted days, so it only warns that they exist.
     */
    public function testItWarnsThatTheHoursCanDifferDuringTheAdjustedDays(): void
    {
        $this->assertEquals(
            $this->expectedText('period-with-adjusted-days-notice'),
            $this->formatter->format($this->placeWithAdjustedDays())
        );
    }

    /**
     * The availability keeps closing the opening hours, so the notice follows it.
     */
    public function testItWarnsAboutTheAdjustedDaysAfterTheAvailability(): void
    {
        $place = $this->placeWithAdjustedDays()
            ->withAvailability(new Status('Unavailable', []), new BookingAvailability('Available'));

        $this->assertEquals(
            $this->expectedText('period-with-adjusted-days-notice-after-the-availability'),
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
            $this->expectedText('period-without-a-notice-for-passed-adjusted-days'),
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

    private function placeWithAdjustedDays(): Offer
    {
        return $this->availablePlace()
            ->withOpeningHours([new OpeningHour(['monday'], '09:00', '16:00')])
            ->withAdjustedDays([$this->autumnHoliday()]);
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
