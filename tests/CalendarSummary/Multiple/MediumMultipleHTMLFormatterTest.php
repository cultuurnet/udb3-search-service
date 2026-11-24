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
use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
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
        Chronos::setTestNow(new Chronos('2021-05-03'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    public function testFormatHTMLMultipleDateMediumOneDay(): void
    {
        $subEvents = Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/sub-events.json'));
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
        $subEvents = Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/sub-events-same-day.json'));
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
        $subEvents = Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/sub-events.json'));
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
        $subEvents = Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/sub-events-more-days.json'));
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
        $subEvents = Json::decodeAssociatively(FileReader::read(__DIR__ . '/data/sub-events.json'));
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

    public function testItHidesOnlyTheDatesThatHaveEnded(): void
    {
        Chronos::setTestNow(new Chronos('2021-05-03T12:00:00+02:00'));
        $past = $this->subEvent('2021-04-01T10:00:00+02:00', '2021-04-01T11:00:00+02:00');
        $endsThisAfternoon = $this->subEvent('2021-05-03T13:00:00+02:00', '2021-05-03T14:00:00+02:00');
        $future = $this->subEvent('2021-06-01T10:00:00+02:00', '2021-06-01T11:00:00+02:00');

        $this->assertSame(
            $this->formatter->format($this->multiple($endsThisAfternoon, $future)),
            (new MediumMultipleHTMLFormatter(new Translator('nl_NL'), true))->format(
                $this->multiple($past, $endsThisAfternoon, $future)
            )
        );
    }

    public function testItShowsADayWithMoreThanOneDateOnce(): void
    {
        $event = $this->multiple(
            $this->subEvent('2021-06-01T10:00:00+02:00', '2021-06-01T11:00:00+02:00'),
            $this->subEvent('2021-06-01T20:00:00+02:00', '2021-06-01T22:00:00+02:00')
        );

        $this->assertSame(
            $this->formatter->format($this->multiple(
                $this->subEvent('2021-06-01T10:00:00+02:00', '2021-06-01T11:00:00+02:00')
            )),
            $this->formatter->format($event)
        );
    }

    private function multiple(Offer ...$subEvents): Offer
    {
        return (new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        ))->withSubEvents($subEvents);
    }

    private function subEvent(string $startDate, string $endDate): Offer
    {
        return new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate)
        );
    }
}
