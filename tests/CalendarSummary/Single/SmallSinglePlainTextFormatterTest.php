<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use Carbon\CarbonImmutable;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarSummaryTester;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SmallSinglePlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var SmallSinglePlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new SmallSinglePlainTextFormatter(new Translator('nl_NL'));
        CalendarSummaryTester::setTestNow(2021, 5, 3);
    }

    public function testFormatPlainTextSingleDateSmOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2018, 1, 25),
            CarbonImmutable::create(2018, 1, 25)->setTime(21, 30)
        );

        $this->assertEquals(
            $this->expectedText('single-date-sm-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testSameWeekInThePast(): void
    {
        CalendarSummaryTester::setTestNow(2021, 5, 5);

        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 4),
            CarbonImmutable::create(2021, 5, 4)->setTime(21, 30)
        );

        $this->assertEquals(
            'Di 4 mei',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmOneDayCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 8, 4),
            CarbonImmutable::create(2021, 8, 4)
        );

        $this->assertEquals(
            'Wo 4 aug',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmToday(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 3)->setTime(11, 30),
            CarbonImmutable::create(2021, 5, 3)->setTime(20, 30)
        );

        $this->assertEquals(
            'Vandaag',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmTonight(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 3)->setTime(18, 30),
            CarbonImmutable::create(2021, 5, 3)->setTime(21, 30)
        );

        $this->assertEquals(
            'Vanavond',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmTomorrow(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 4)->setTime(18, 30),
            CarbonImmutable::create(2021, 5, 4)->setTime(21, 30)
        );

        $this->assertEquals(
            'Morgen',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleCurrentWeek(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 9)->setTime(18, 30),
            CarbonImmutable::create(2021, 5, 9)->setTime(18, 30)
        );

        $this->assertEquals(
            'Deze zondag',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmWithLeadingZeroOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2018, 1, 8)->setTime(0, 0),
            CarbonImmutable::create(2018, 1, 8)->setTime(21, 30)
        );

        $this->assertEquals(
            'Ma 8 jan 2018',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            'Do 25 jan - zo 28 jan',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmWithLeadingZeroMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $this->assertEquals(
            'Za 6 jan - ma 8 jan',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmOneDayWithStatusUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            'Do 25 jan 2018 (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmOneDayWithStatusTemporarilyUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            'Do 25 jan 2018 (uitgesteld)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmMoreDaysWithStatusUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            'Do 25 jan - zo 28 jan (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmMoreDaysWithStatusUnavailableAndBookingUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            'Do 25 jan - zo 28 jan (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmMoreDaysWithStatusAvailableAndBookingUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            'Do 25 jan - zo 28 jan (Volzet of uitverkocht)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateSmMoreDaysWithStatusTemporarilyUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            'Do 25 jan - zo 28 jan (uitgesteld)',
            $this->formatter->format($event)
        );
    }
}
