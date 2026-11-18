<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\FileReader;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LargeMultiplePlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var LargeMultiplePlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        date_default_timezone_set('Europe/Brussels');
        $this->formatter = new LargeMultiplePlainTextFormatter(new Translator('nl_NL'), false);
    }

    public function testFormatPlainTextMultipleDateLargeOneDay(): void
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
            $this->expectedText('multiple-date-large-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextMultipleDateLargeOneDayWithUnavailableStatus(): void
    {
        $subEvents = json_decode(FileReader::read(__DIR__ . '/data/sub-events.json'), true);
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        );

        $newEvents = [];
        foreach ($subEvents as $subEvent) {
            $newEvents[] = new Offer(
                OfferType::event(),
                new Status('Unavailable', []),
                new BookingAvailability('Available'),
                new DateTimeImmutable($subEvent['startDate']),
                new DateTimeImmutable($subEvent['endDate'])
            );
        }

        $event = $event->withSubEvents($newEvents);

        $this->assertEquals(
            $this->expectedText('multiple-date-large-one-day-with-unavailable-status'),
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextMultipleDateLargeMoreDays(): void
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
            $this->expectedText('multiple-date-large-more-days'),
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextMultipleDaysFrench(): void
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
            $this->expectedText('multiple-days-french'),
            (new LargeMultiplePlainTextFormatter(new Translator('fr'), false))->format($event)
        );
    }

    public function testItWillShowEventHasConcludedWhenAllPastDatesAreHidden(): void
    {
        $formatter = new LargeMultiplePlainTextFormatter(new Translator('nl_NL'), true);

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

        $expectedOutput = 'Evenement afgelopen';

        $this->assertEquals(
            $expectedOutput,
            $formatter->format($event)
        );
    }

    /**
     * Every sub-event already is a line of its own, so its childcare gets one too instead
     * of trailing the date it belongs to.
     */
    public function testItGivesTheChildcareOfASubEventALineOfItsOwn(): void
    {
        $subEvents = json_decode(FileReader::read(__DIR__ . '/data/sub-events-with-childcare.json'), true);
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
            $this->expectedText('multiple-dates-with-childcare-and-overnight'),
            $this->formatter->format($event)
        );
    }

    /**
     * The availability keeps closing the date it belongs to, so the childcare of that date
     * follows it on a line of its own instead of splitting the two apart.
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
            $this->expectedText('multiple-dates-with-childcare-and-an-unavailable-status'),
            $this->formatter->format($event)
        );
    }
}
