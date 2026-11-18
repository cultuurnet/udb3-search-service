<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SmallMultipleHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var SmallMultipleHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new SmallMultipleHTMLFormatter(new Translator('nl_NL'));
        Chronos::setTestNow(new Chronos('2021-05-03'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    public function testFormatMultipleOnSamedayToday(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-03 11:30'),
            new Chronos('2021-05-03 20:30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '<span class="cf-days">Vandaag</span>',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleOnSamedayTonight(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-03 18:30'),
            new Chronos('2021-05-03 20:30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '<span class="cf-days">Vanavond</span>',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleOnSamedayTomorrow(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-04 11:30'),
            new Chronos('2021-05-04 20:30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '<span class="cf-days">Morgen</span>',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleOnSamedayThisWeek(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-07 11:30'),
            new Chronos('2021-05-07 20:30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            '<span class="cf-meta">Deze</span> <span class="cf-days">vrijdag</span>',
            $this->formatter->format($offer)
        );
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

    public function testFormatMultipleCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-11-25'),
            new Chronos('2021-11-30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            $this->expectedHtml('multiple-current-year'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleStartsCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-11-25'),
            new Chronos('2030-11-30'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            $this->expectedHtml('multiple-starts-current-year'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleEndsCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2020'),
            new DateTimeImmutable('30-11-2021'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            $this->expectedHtml('multiple-ends-current-year'),
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

    public function testFormatAMultipleWithSameBeginAndEndDate(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('08-10-2025'),
            new DateTimeImmutable('08-10-2025'),
            CalendarType::multiple()
        );

        $output = '<span class="cf-weekday cf-meta">Wo</span>';
        $output .= ' ';
        $output .= '<span class="cf-date">8 okt 2025</span>';

        $this->assertEquals(
            $output,
            $this->formatter->format($offer)
        );
    }
}
