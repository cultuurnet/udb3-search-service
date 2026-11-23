<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ExtraSmallMultiplePlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var ExtraSmallMultiplePlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExtraSmallMultiplePlainTextFormatter(new Translator('nl_NL'));
        Chronos::setTestNow(new Chronos('2021-05-03'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    public function testFormatMultipleWithoutLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            $this->expectedText('multiple-without-leading-zeroes'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleWithLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-03-2025'),
            new DateTimeImmutable('08-03-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '4 mrt 2025 - 8 mrt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '25 nov 2025 - 30 nov 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleOnSameDayWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('30-11-2030'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '30 nov 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleDayWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-03-2025'),
            new DateTimeImmutable('30-03-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '25 mrt 2025 - 30 mrt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleDayStartCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-03-25'),
            new Chronos('2030-03-30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '25 mrt - 30 mrt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleDayEndCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2020-03-25'),
            new Chronos('2021-03-30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '25 mrt 2020 - 30 mrt',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleMonthWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-10-2025'),
            new DateTimeImmutable('08-10-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '4 okt 2025 - 8 okt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithSameBeginAndEndDate(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('08-03-2025'),
            new DateTimeImmutable('08-03-2025'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '8 mrt 2025',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithSameBeginAndEndDateCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-03-08 11:00'),
            new Chronos('2021-03-08 19:00'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '8 mrt',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleMonthWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-10-2025'),
            new DateTimeImmutable('08-10-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '4 okt 2025 - 8 okt 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleWithUnavailableStatusAndUnavailableBooking(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('04-10-2025'),
            new DateTimeImmutable('08-10-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '4 okt 2025 - 8 okt 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleAvailableStatusAndUnavailableBooking(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('04-10-2025'),
            new DateTimeImmutable('08-10-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '4 okt 2025 - 8 okt 2030 (Volzet of uitverkocht)',
            $this->formatter->format($offer)
        );
    }
}
