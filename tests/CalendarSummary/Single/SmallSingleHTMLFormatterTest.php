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

final class SmallSingleHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var SmallSingleHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new SmallSingleHTMLFormatter(new Translator('nl_NL'));
        Chronos::setTestNow(new Chronos('2021-05-03'));
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
            '<span class="cf-weekday cf-meta">Do</span> <span class="cf-date">25</span> <span class="cf-month">jan</span> <span class="cf-year">2018</span>',
            $this->formatter->format($event)
        );
    }

    public function testFormatHtmlSingleDateSmOneDayCurrentYear(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-24'),
            new Chronos('2021-05-24')
        );

        $this->assertEquals(
            '<span class="cf-weekday cf-meta">Ma</span> <span class="cf-date">24</span> <span class="cf-month">mei</span>',
            $this->formatter->format($event)
        );
    }


    public function testFormatHTMLSingleDateXsToday(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-03 11:30'),
            new Chronos('2021-05-03 20:30')
        );

        $this->assertEquals(
            '<span class="cf-days">Vandaag</span>',
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsTonight(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-03 18:30'),
            new Chronos('2021-05-03 21:30')
        );

        $this->assertEquals(
            '<span class="cf-days">Vanavond</span>',
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLSingleDateXsTomorrow(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-04 18:30'),
            new Chronos('2021-05-04 21:30')
        );

        $this->assertEquals(
            '<span class="cf-days">Morgen</span>',
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleCurrentWeek(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new Chronos('2021-05-09 18:30'),
            new Chronos('2021-05-09 18:30')
        );

        $this->assertEquals(
            '<span class="cf-meta">Deze</span> <span class="cf-days">zondag</span>',
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
            '<span class="cf-weekday cf-meta">Do</span> <span class="cf-date">25</span> <span class="cf-month">jan</span> <span class="cf-year">2018</span> <span class="cf-status">(geannuleerd)</span>',
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
            '<span class="cf-weekday cf-meta">Do</span> <span class="cf-date">25</span> <span class="cf-month">jan</span> <span class="cf-year">2018</span> <span title="Covid-19" class="cf-status">(geannuleerd)</span>',
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
            '<span class="cf-weekday cf-meta">Ma</span> <span class="cf-date">8</span> <span class="cf-month">jan</span> <span class="cf-year">2018</span>',
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
