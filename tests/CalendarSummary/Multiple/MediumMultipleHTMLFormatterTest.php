<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\FileReader;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MediumMultipleHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var MediumMultipleHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new MediumMultipleHTMLFormatter(new Translator('nl_NL'), false);
    }

    public function testFormatHTMLMultipleDateMediumOneDay(): void
    {
        $subEvents = json_decode(FileReader::read(__DIR__ . '/data/sub-events.json'), true);
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        );

        $newEvents = [];
        foreach ($subEvents as $subEvent) {
            $newEvents[] = new Offer(
                OfferType::event(),
                new Status('Available', []),
                new BookingAvailability('Available'),
                new DateTimeImmutable($subEvent['startDate']),
                new DateTimeImmutable($subEvent['endDate'])
            );
        }

        $event = $event->withSubEvents($newEvents);

        $this->assertEquals(
            $this->expectedHtml('multiple-date-medium-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLMultipleOnSameDay(): void
    {
        $subEvents = json_decode(FileReader::read(__DIR__ . '/data/sub-events-same-day.json'), true);
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        );

        $newEvents = [];
        foreach ($subEvents as $subEvent) {
            $newEvents[] = new Offer(
                OfferType::event(),
                new Status('Available', []),
                new BookingAvailability('Available'),
                new DateTimeImmutable($subEvent['startDate']),
                new DateTimeImmutable($subEvent['endDate'])
            );
        }

        $event = $event->withSubEvents($newEvents);

        $this->assertEquals(
            $this->expectedHtml('multiple-on-same-day'),
            $this->formatter->format($event)
        );
    }


    public function testFormatHTMLMultipleDateMediumOneDayWithUnavailableStatus(): void
    {
        $subEvents = json_decode(FileReader::read(__DIR__ . '/data/sub-events.json'), true);
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        );

        $newEvents = [];
        foreach ($subEvents as $subEvent) {
            $newEvents[] = new Offer(
                OfferType::event(),
                new Status('Available', []),
                new BookingAvailability('Available'),
                new DateTimeImmutable($subEvent['startDate']),
                new DateTimeImmutable($subEvent['endDate'])
            );
        }
        $newEvents[1] = $newEvents[1]->withAvailability(
            new Status('Unavailable', []),
            new BookingAvailability('Unavailable')
        );
        $newEvents[2] = $newEvents[2]->withAvailability(
            new Status('Available', []),
            new BookingAvailability('Unavailable')
        );
        $event = $event->withSubEvents($newEvents);

        $this->assertEquals(
            $this->expectedHtml('multiple-date-medium-one-day-with-unavailable-status'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLMultipleDateMediumMoreDays(): void
    {
        $subEvents = json_decode(FileReader::read(__DIR__ . '/data/sub-events-more-days.json'), true);
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        );

        $newEvents = [];
        foreach ($subEvents as $subEvent) {
            $newEvents[] = new Offer(
                OfferType::event(),
                new Status('Available', []),
                new BookingAvailability('Available'),
                new DateTimeImmutable($subEvent['startDate']),
                new DateTimeImmutable($subEvent['endDate'])
            );
        }

        $event = $event->withSubEvents($newEvents);

        $this->assertEquals(
            $this->expectedHtml('multiple-date-medium-more-days'),
            $this->formatter->format($event)
        );
    }

    public function testItWillShowEventHasConcludedWhenAllPastDatesAreHidden(): void
    {
        $formatter = new MediumMultipleHTMLFormatter(new Translator('nl_NL'), true);
        $subEvents = json_decode(FileReader::read(__DIR__ . '/data/sub-events.json'), true);
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        );

        $newEvents = [];
        foreach ($subEvents as $subEvent) {
            $newEvents[] = new Offer(
                OfferType::event(),
                new Status('Available', []),
                new BookingAvailability('Available'),
                new DateTimeImmutable($subEvent['startDate']),
                new DateTimeImmutable($subEvent['endDate'])
            );
        }

        $event = $event->withSubEvents($newEvents);
        $this->assertEquals(
            '<span>Evenement afgelopen</span>',
            $formatter->format($event)
        );
    }
}
