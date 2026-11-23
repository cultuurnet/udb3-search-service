<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\AdjustedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\ClosedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CalendarPlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var CalendarPlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new CalendarPlainTextFormatter();
    }

    public function testGeneralFormatMethod(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00'),
            CalendarType::single()
        );

        $this->assertSame('25 jan 2018', $this->formatter->format($offer, 'xs'));
    }

    public function testExtraLargeFormatRendersTheAdjustedAndClosedDays(): void
    {
        $place = (new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        ))->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2030-11-02'),
                    new DateTimeImmutable('2030-11-02'),
                    new OpeningHours(),
                    ['nl' => 'Herfstvakantie']
                ),
            ]
        )->withClosedDays(
            [
                new ClosedDay(
                    new DateTimeImmutable('2030-12-25'),
                    new DateTimeImmutable('2030-12-25'),
                    ['nl' => 'Kerstmis']
                ),
            ]
        );

        $this->assertSame(
            $this->expectedText('extra-large-format-renders-the-adjusted-and-closed-days') . PHP_EOL,
            $this->formatter->format($place, 'xl')
        );

        // The large format does not know about closed days, and without opening hours it
        // has nothing the adjusted days could differ from either.
        $this->assertSame(
            'Alle dagen open' . PHP_EOL,
            $this->formatter->format($place, 'lg')
        );
    }

    /**
     * Single and multiple have no extra large output of their own, so 'xl' has to
     * be accepted and fall back to the large format.
     */
    public function testExtraLargeFormatEqualsTheLargeFormatForSingle(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00'),
            CalendarType::single()
        );

        $this->assertSame(
            $this->formatter->format($event, 'lg'),
            $this->formatter->format($event, 'xl')
        );
    }

    public function testExtraLargeFormatEqualsTheLargeFormatForMultiple(): void
    {
        $event = (new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::multiple()
        ))->withSubEvents(
            [
                new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Available'),
                    new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
                    new DateTimeImmutable('2018-01-25T21:30:00+01:00')
                ),
                new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Available'),
                    new DateTimeImmutable('2018-02-01T20:00:00+01:00'),
                    new DateTimeImmutable('2018-02-01T21:30:00+01:00')
                ),
            ]
        );

        $this->assertSame(
            $this->formatter->format($event, 'lg'),
            $this->formatter->format($event, 'xl')
        );
    }

    /**
     * Periodic does have an extra large output of its own, so unlike single and
     * multiple 'xl' has to route to a different formatter than 'lg'.
     */
    public function testExtraLargeFormatRendersTheAdjustedDaysForPeriodic(): void
    {
        $place = (new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2030-01-01'),
            new DateTimeImmutable('2030-12-31'),
            CalendarType::periodic()
        ))->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2030-11-02'),
                    new DateTimeImmutable('2030-11-02'),
                    new OpeningHours(),
                    ['nl' => 'Herfstvakantie']
                ),
            ]
        );

        $this->assertStringContainsString('Herfstvakantie', $this->formatter->format($place, 'xl'));
        $this->assertStringNotContainsString('Herfstvakantie', $this->formatter->format($place, 'lg'));
    }

    public function testGeneralFormatMethodAndCatchException(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00'),
            CalendarType::single()
        );

        $this->expectException(FormatterException::class);
        $this->formatter->format($offer, 'sx');
    }
}
