<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LargeMultipleHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var LargeMultipleHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        date_default_timezone_set('Europe/Brussels');
        $this->formatter = new LargeMultipleHTMLFormatter(new Translator('nl_NL'), false);
    }

    public function testFormatHTMLMultipleDateLargeOneDay(): void
    {
        $subEvents = json_decode(file_get_contents(__DIR__ . '/data/sub-events.json'), true);

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
            $this->expectedHtml('multiple-date-large-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHtmlMultipleDaysFrench(): void
    {
        $subEvents = json_decode(file_get_contents(__DIR__ . '/data/sub-events-more-days.json'), true);
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
            $this->expectedHtml('multiple-days-french'),
            (new LargeMultipleHTMLFormatter(new Translator('fr'), false))->format($event)
        );
    }

    public function testFormatHTMLMultipleDateLargeOneDayWithUnavailability(): void
    {
        $subEvents = json_decode(file_get_contents(__DIR__ . '/data/sub-events.json'), true);
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
            $this->expectedHtml('multiple-date-large-one-day-with-unavailability'),
            $this->formatter->format($event)
        );
    }

    public function testFormatHTMLMultipleDateLargeMoreDays(): void
    {
        $subEvents = json_decode(file_get_contents(__DIR__ . '/data/sub-events-more-days.json'), true);
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
            $this->expectedHtml('multiple-date-large-more-days'),
            $this->formatter->format($event)
        );
    }

    public function testItWillShowEventHasConcludedWhenAllPastDatesAreHidden(): void
    {
        $formatter = new LargeMultipleHTMLFormatter(new Translator('nl_NL'), true);
        $subEvents = json_decode(file_get_contents(__DIR__ . '/data/sub-events.json'), true);
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

    /**
     * Every sub-event has a list item of its own, so its childcare is nested inside of it.
     */
    public function testItNestsTheChildcareInsideTheSubEventItBelongsTo(): void
    {
        $subEvents = json_decode(file_get_contents(__DIR__ . '/data/sub-events-with-childcare.json'), true);
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
            $newEvents[] = (new Offer(
                OfferType::event(),
                new Status('Available', []),
                new BookingAvailability('Available'),
                new DateTimeImmutable($subEvent['startDate']),
                new DateTimeImmutable($subEvent['endDate'])
            ))
                ->withChildcare(Childcare::fromArrayOrNull($subEvent['childcare'] ?? null))
                ->withOvernight($subEvent['overnight'] ?? false);
        }

        $event = $event->withSubEvents($newEvents);

        $this->assertEquals(
            $this->expectedHtml('multiple-dates-with-childcare-and-overnight'),
            $this->formatter->format($event)
        );
    }

    /**
     * The availability stays the last inline element of the date it belongs to, so the
     * nested list of the childcare follows it instead of splitting the two apart.
     */
    public function testItKeepsTheAvailabilityBetweenTheDateAndItsChildcare(): void
    {
        $childcare = new Childcare('07:00', '18:00');

        $event = (new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        ))->withSubEvents(
            [
                (new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Available'),
                    new DateTimeImmutable('2026-08-13T08:00:00+02:00'),
                    new DateTimeImmutable('2026-08-13T17:00:00+02:00')
                ))->withChildcare($childcare),
                (new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Available'),
                    new DateTimeImmutable('2026-09-09T08:00:00+02:00'),
                    new DateTimeImmutable('2026-09-09T17:00:00+02:00')
                ))
                    ->withChildcare($childcare)
                    ->withAvailability(new Status('Unavailable', []), new BookingAvailability('Available')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('multiple-dates-with-childcare-and-an-unavailable-status'),
            $this->formatter->format($event)
        );
    }
}
