<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ExtraSmallSingleHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var ExtraSmallSingleHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new ExtraSmallSingleHTMLFormatter(new Translator('nl_NL'));
        Chronos::setTestNow(new Chronos('2021-05-09'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    public function testFormatHTMLSingleDateXsOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsOneDayWithStatusUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-one-day-with-status-unavailable'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHtmlSingleDateCurrentYEar(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-01-08 01:00'),
            new Chronos('2021-01-08 21:30')
        );

        $this->assertEquals(
            '<span class="cf-date">8</span> <span class="cf-month">jan</span>',
            $this->formatter->format($event)
        );
    }

    public function testFormatHtmlSingleDateXsMoreDaysCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-01-23 01:00'),
            new Chronos('2021-01-28 21:30')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-more-days-current-year'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHtmlSingleDateXsMoreDaysStartCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-12-23 01:00'),
            new Chronos('2022-01-28 21:30')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-more-days-start-current-year'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHtmlSingleDateXsMoreDaysEndCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2020-12-23 01:00'),
            new Chronos('2021-01-28 21:30')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-more-days-end-current-year'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsOneDayWithStatusUnavailableAndReason(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', ['nl' => 'Covid-19']),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-one-day-with-status-unavailable-and-reason'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsWithLeadingZeroOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-08T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $this->assertEquals(
            '<span class="cf-date">8</span> <span class="cf-month">jan</span> <span class="cf-year">2018</span>',
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-27T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-more-days'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsMoreDaysWithUnavailableStatus(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-27T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-more-days-with-unavailable-status'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsWithLeadingZeroMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedHtml('single-date-xs-with-leading-zero-more-days'),
            $this->formatter->format($event)
        );
    }
}
