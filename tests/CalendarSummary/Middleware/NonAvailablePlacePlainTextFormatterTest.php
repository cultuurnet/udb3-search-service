<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Middleware;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use PHPUnit\Framework\TestCase;

final class NonAvailablePlacePlainTextFormatterTest extends TestCase
{
    /**
     * @var NonAvailablePlacePlainTextFormatter
     */
    private $formatter;

    protected function setUp(): void
    {
        $translator = new Translator('nl_BE');
        $this->formatter = new NonAvailablePlacePlainTextFormatter($translator);
    }

    public function testWillInterceptUnavailablePlace(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Unavailable', []),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $place,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('Permanent gesloten', $result);
    }

    public function testWillInterceptTemporarilyUnavailablePlace(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $place,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('Tijdelijk gesloten', $result);
    }

    public function testWillIgnoreAvailablePlaces(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $place,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('foo', $result);
    }

    public function testWillIgnoreUnavailableEvents(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $event,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('foo', $result);
    }

    public function testWillIgnoreTemporarilyUnavailableEvents(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $event,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('foo', $result);
    }

    public function testWillIgnoreAvailableEvents(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $event,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('foo', $result);
    }
}
