<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use Carbon\CarbonImmutable;
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

final class ExtraSmallMultipleHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var ExtraSmallMultipleHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExtraSmallMultipleHTMLFormatter(new Translator('nl_NL'));
        CalendarSummaryTester::setTestNow(2021, 5, 3);
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
            $this->expectedHtml('multiple-without-leading-zeroes'),
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
            $this->expectedHtml('multiple-with-leading-zeroes'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleWithoutLeadingZeroesWithUnavailableStatus(): void
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
            $this->expectedHtml('multiple-without-leading-zeroes-with-unavailable-status'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleWithoutLeadingZeroesWithUnavailableStatusAndUnavailableBooking(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            $this->expectedHtml('multiple-without-leading-zeroes-with-unavailable-status-and-unavailable-booking'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleWithoutLeadingZeroesWithAvailableStatusAndUnavailableBooking(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            $this->expectedHtml('multiple-without-leading-zeroes-with-available-status-and-unavailable-booking'),
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
            $this->expectedHtml('multiple-month-without-leading-zero'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatAMultipleWithSameBeginAndEndDay(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('08-10-2025 12:00'),
            new DateTimeImmutable('08-10-2025 14:00'),
            CalendarType::multiple()
        );

        $output = '<span class="cf-date">8</span> <span class="cf-month">okt</span> <span class="cf-year">2025</span>';

        $this->assertEquals(
            $output,
            $this->formatter->format($offer)
        );
    }

    public function testFormatAMultipleWithSameBeginAndEndDayCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 10, 8)->setTime(12, 0),
            CarbonImmutable::create(2021, 10, 8)->setTime(14, 0),
            CalendarType::multiple()
        );

        $output = '<span class="cf-date">8</span> <span class="cf-month">okt</span>';

        $this->assertEquals(
            $output,
            $this->formatter->format($offer)
        );
    }
}
