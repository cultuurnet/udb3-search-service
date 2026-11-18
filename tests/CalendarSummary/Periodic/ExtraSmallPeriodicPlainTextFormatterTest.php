<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\CalendarSummaryTester;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ExtraSmallPeriodicPlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var ExtraSmallPeriodicPlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExtraSmallPeriodicPlainTextFormatter(new Translator('nl_NL'));
        CalendarSummaryTester::setTestNow(2021, 5, 3);
    }

    public function testFormatAPeriodWithoutLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            $this->expectedText('period-without-leading-zeroes'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-03-2025'),
            new DateTimeImmutable('08-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Vanaf 4 mrt 2025',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithoutLeadingZeroesWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Vanaf 25 nov 2025 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodStartsCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2021'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Vanaf 25 nov',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodEndsCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2015'),
            new DateTimeImmutable('30-11-2021'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Tot 30 nov',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodDayWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-03-2025'),
            new DateTimeImmutable('30-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Vanaf 25 mrt 2025',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodMonthWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-10-2025'),
            new DateTimeImmutable('08-10-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Vanaf 4 okt 2025',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodThatHasAlreadyStarted(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('12-03-2015'),
            new DateTimeImmutable('18-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Tot 18 mrt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodThatHasAlreadyStartedWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('12-03-2015'),
            new DateTimeImmutable('18-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Tot 18 mrt 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodThatStartsLaterToday(): void
    {
        CalendarSummaryTester::setTestNow(2021, 5, 3, 10, 0);

        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('03-05-2021 20:00'),
            new DateTimeImmutable('18-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Tot 18 mrt 2030',
            $this->formatter->format($offer)
        );
    }
}
