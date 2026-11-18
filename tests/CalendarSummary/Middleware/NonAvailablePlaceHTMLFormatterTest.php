<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Middleware;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use PHPUnit\Framework\TestCase;

final class NonAvailablePlaceHTMLFormatterTest extends TestCase
{
    /**
     * @var NonAvailablePlaceHTMLFormatter
     */
    private $formatter;

    protected function setUp(): void
    {
        $translator = new Translator('nl_BE');
        $this->formatter = new NonAvailablePlaceHTMLFormatter($translator);
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

        $this->assertEquals('<span class="cf-status">Permanent gesloten</span>', $result);
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

        $this->assertEquals('<span class="cf-status">Tijdelijk gesloten</span>', $result);
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

    public function testItWillAddTitleAttributeWithReason(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Unavailable', ['nl' => 'Covid-19']),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $place,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('<span title="Covid-19" class="cf-status">Permanent gesloten</span>', $result);
    }

    public function testItWillNotAddTitleAttributeWhenReasonIsNotAvailableInCorrectLanguage(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Unavailable', ['fr' => "Désolé, c'est annulé!"]),
            new BookingAvailability('Available')
        );

        $result = $this->formatter->format(
            $place,
            function () {
                return 'foo';
            }
        );

        $this->assertEquals('<span class="cf-status">Permanent gesloten</span>', $result);
    }
}
