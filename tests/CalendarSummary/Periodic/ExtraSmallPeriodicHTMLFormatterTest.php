<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\CalendarSummaryTester;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ExtraSmallPeriodicHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var ExtraSmallPeriodicHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExtraSmallPeriodicHTMLFormatter(new Translator('nl_NL'));
        CalendarSummaryTester::setTestNow(2021, 5, 3);
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
            $this->expectedHtml('period-starts-current-year'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodEndsCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-01-2020'),
            new DateTimeImmutable('30-01-2021'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            $this->expectedHtml('period-ends-current-year'),
            $this->formatter->format($offer)
        );
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
            $this->expectedHtml('period-without-leading-zeroes'),
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
            $this->expectedHtml('period-with-leading-zeroes'),
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
            $this->expectedHtml('period-without-leading-zeroes-with-unavailable-status'),
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
            $this->expectedHtml('period-day-without-leading-zero'),
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
            $this->expectedHtml('period-month-without-leading-zero'),
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
            $this->expectedHtml('period-that-has-already-started'),
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
            $this->expectedHtml('period-that-has-already-started'),
            $this->formatter->format($offer)
        );
    }
}
