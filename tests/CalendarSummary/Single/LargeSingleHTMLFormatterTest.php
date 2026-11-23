<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LargeSingleHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var LargeSingleHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        date_default_timezone_set('Europe/Brussels');
        $this->formatter = new LargeSingleHTMLFormatter(new Translator('nl_NL'));
    }

    public function testFormatHTMLSingleDateLargeOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateLargeWithLeadingZeroOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-08T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-with-leading-zero-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHtmlSingleFrench(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-08-12T16:00+01:00'),
            new DateTimeImmutable('2021-08-12T21:00:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-french'),
            (new LargeSingleHTMLFormatter(new Translator('fr')))->format($event)
        );
    }

    public function testFormatHtmlSingleMultipleDaysFrench(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-08-12T16:00+01:00'),
            new DateTimeImmutable('2021-08-14T21:00:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-multiple-days-french'),
            (new LargeSingleHTMLFormatter(new Translator('fr')))->format($event)
        );
    }

    public function testFormatHTMLSingleDateLargeMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-more-days'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateLargeWithLeadingZerosMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-with-leading-zeros-more-days'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateLargeWholeDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T00:00:00+01:00'),
            new DateTimeImmutable('2018-01-06T23:59:59+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-whole-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateLargeWholeDayWithStatusUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T00:00:00+01:00'),
            new DateTimeImmutable('2018-01-06T23:59:59+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-whole-day-with-status-unavailable'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateLargeWholeDayWithStatusUnavailableAndBookingUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-06T00:00:00+01:00'),
            new DateTimeImmutable('2018-01-06T23:59:59+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-whole-day-with-status-unavailable-and-booking-unavailable'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateLargeWholeDayWithStatusAvailableAndBookingUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-06T00:00:00+01:00'),
            new DateTimeImmutable('2018-01-06T23:59:59+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-large-whole-day-with-status-available-and-booking-unavailable'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateSameTime(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T13:30:00+01:00'),
            new DateTimeImmutable('2018-01-06T13:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-same-time'),
            $this->formatter->format($event)
        );
    }

    public function testItShowsTheChildcare(): void
    {
        $this->assertEquals(
            $this->expectedHtml('single-with-childcare'),
            $this->formatter->format($this->camp()->withChildcare(new Childcare('07:00', '18:00')))
        );
    }

    public function testItShowsChildcareThatOnlyHappensBeforeTheOpeningHours(): void
    {
        $this->assertEquals(
            $this->expectedHtml('single-with-childcare-before-the-opening-hours'),
            $this->formatter->format($this->camp()->withChildcare(new Childcare('07:00', null)))
        );
    }

    public function testItShowsChildcareThatOnlyHappensAfterTheOpeningHours(): void
    {
        $this->assertEquals(
            $this->expectedHtml('single-with-childcare-after-the-opening-hours'),
            $this->formatter->format($this->camp()->withChildcare(new Childcare(null, '18:00')))
        );
    }

    public function testItShowsTheOvernightStayWithoutChildcare(): void
    {
        $this->assertEquals(
            $this->expectedHtml('single-with-overnight'),
            $this->formatter->format($this->camp()->withOvernight(true))
        );
    }

    /**
     * Only the overnight stay is capitalized when it is combined with the childcare.
     */
    public function testItCombinesTheOvernightStayAndTheChildcare(): void
    {
        $this->assertEquals(
            $this->expectedHtml('single-with-childcare-and-overnight'),
            $this->formatter->format($this->campWithChildcareAndOvernight())
        );
    }

    /**
     * The availability keeps closing the summary, so it stays the last thing that is read.
     */
    public function testItShowsTheChildcareBeforeTheAvailability(): void
    {
        $event = $this->campWithChildcareAndOvernight()
            ->withAvailability(new Status('Unavailable', []), new BookingAvailability('Available'));

        $this->assertEquals(
            $this->expectedHtml('single-with-childcare-and-a-cancelled-status'),
            $this->formatter->format($event)
        );
    }

    public function testItTranslatesTheChildcareAndTheOvernightStay(): void
    {
        $this->assertEquals(
            $this->expectedHtml('single-with-childcare-and-overnight-in-french'),
            (new LargeSingleHTMLFormatter(new Translator('fr')))->format($this->campWithChildcareAndOvernight())
        );
    }

    private function camp(): Offer
    {
        return new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2026-08-13T08:00:00+02:00'),
            new DateTimeImmutable('2026-08-13T17:00:00+02:00')
        );
    }

    private function campWithChildcareAndOvernight(): Offer
    {
        return $this->camp()
            ->withChildcare(new Childcare('07:00', '18:00'))
            ->withOvernight(true);
    }
}
