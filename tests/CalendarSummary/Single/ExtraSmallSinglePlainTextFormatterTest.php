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

final class ExtraSmallSinglePlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var ExtraSmallSinglePlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExtraSmallSinglePlainTextFormatter(new Translator('nl_NL'));
        CalendarSummaryTester::setTestNow(2021, 5, 9);
    }

    public function testFormatPlainTextSingleDateXsOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedText('single-date-xs-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsWithLeadingZeroOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-08T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $this->assertEquals(
            '8 jan 2018',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateCurrentYEar(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new CarbonImmutable('2021-01-08 01:00'),
            new CarbonImmutable('2021-01-08 21:30')
        );

        $this->assertEquals(
            '8 jan',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDaysCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new CarbonImmutable('2021-01-23 01:00'),
            new CarbonImmutable('2021-01-28 21:30')
        );

        $this->assertEquals(
            '23 jan - 28 jan',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDaysStartCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new CarbonImmutable('2021-12-23 01:00'),
            new CarbonImmutable('2022-01-28 21:30')
        );

        $this->assertEquals(
            '23 dec - 28 jan 2022',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDaysEndCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new CarbonImmutable('2020-12-23 01:00'),
            new CarbonImmutable('2021-01-28 21:30')
        );

        $this->assertEquals(
            '23 dec 2020 - 28 jan',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            '25 jan 2018 - 28 jan 2018',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsWithLeadingZeroMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $this->assertEquals(
            '6 jan 2018 - 8 jan 2018',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsOneDayWithStatusUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            '25 jan 2018 (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsOneDayWithStatusTemporarilyUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            '25 jan 2018 (uitgesteld)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDaysWithStatusUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            '25 jan 2018 - 28 jan 2018 (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDaysWithStatusUnavailableAndBookingUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            '25 jan 2018 - 28 jan 2018 (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDaysWithStatusAvailableAndBookingUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            '25 jan 2018 - 28 jan 2018 (Volzet of uitverkocht)',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateXsMoreDaysWithStatusTemporarilyUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            '25 jan 2018 - 28 jan 2018 (uitgesteld)',
            $this->formatter->format($event)
        );
    }
}
