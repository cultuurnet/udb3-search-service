<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Single;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LargeSinglePlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var LargeSinglePlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        date_default_timezone_set('Europe/Brussels');
        $this->formatter = new LargeSinglePlainTextFormatter(new Translator('nl_NL'));
    }

    public function testFormatPlainTextSingleDateLargeOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $this->assertEquals(
            $this->expectedText('single-date-large-one-day'),
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeWithLeadingZeroOneDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-08T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $expectedOutput = 'Maandag 8 januari 2018 van 20:00 tot 21:30';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleFrench(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-08-12T16:00+01:00'),
            new DateTimeImmutable('2021-08-12T21:00:00+01:00')
        );

        $expectedOutput = 'Jeudi 12 août 2021 de 17:00 à 22:00';

        $this->assertEquals(
            $expectedOutput,
            (new LargeSinglePlainTextFormatter(new Translator('fr')))->format($event)
        );
    }

    public function testFormatPlainTextSingleMultipleDaysFrench(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-08-12T16:00+01:00'),
            new DateTimeImmutable('2021-08-14T21:00:00+01:00')
        );

        $expectedOutput = 'Du jeudi 12 août 2021 à 17:00 au samedi 14 août 2021 à 22:00';

        $this->assertEquals(
            $expectedOutput,
            (new LargeSinglePlainTextFormatter(new Translator('fr')))->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeOneDayWithUnavailableStatus(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $expectedOutput = 'Donderdag 25 januari 2018 van 20:00 tot 21:30 (geannuleerd)';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeOneDayWithUnavailableStatusAndUnavailableBooking(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $expectedOutput = 'Donderdag 25 januari 2018 van 20:00 tot 21:30 (geannuleerd)';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeOneDayWithAvailableStatusAndUnavailableBooking(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-25T21:30:00+01:00')
        );

        $expectedOutput = 'Donderdag 25 januari 2018 van 20:00 tot 21:30 (Volzet of uitverkocht)';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-28T21:30:00+01:00')
        );

        $expectedOutput = 'Van donderdag 25 januari 2018 om 20:00 tot en met zondag 28 januari 2018 om 21:30';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeWithLeadingZeroMoreDays(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T20:00:00+01:00'),
            new DateTimeImmutable('2018-01-08T21:30:00+01:00')
        );

        $expectedOutput = 'Van zaterdag 6 januari 2018 om 20:00 tot en met maandag 8 januari 2018 om 21:30';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeWholeDay(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T00:00:00+01:00'),
            new DateTimeImmutable('2018-01-06T23:59:59+01:00')
        );

        $expectedOutput = 'Zaterdag 6 januari 2018';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testFormatPlainTextSingleDateLargeSameTime(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2018-01-06T13:30:00+01:00'),
            new DateTimeImmutable('2018-01-06T13:30:00+01:00')
        );

        $expectedOutput = 'Zaterdag 6 januari 2018 om 13:30';

        $this->assertEquals(
            $expectedOutput,
            $this->formatter->format($event)
        );
    }

    public function testItShowsTheChildcare(): void
    {
        $this->assertEquals(
            'Donderdag 13 augustus 2026 van 08:00 tot 17:00 (opvang van 7:00 tot 18:00)',
            $this->formatter->format($this->camp()->withChildcare(new Childcare('07:00', '18:00')))
        );
    }

    public function testItShowsChildcareThatOnlyHappensBeforeTheOpeningHours(): void
    {
        $this->assertEquals(
            'Donderdag 13 augustus 2026 van 08:00 tot 17:00 (vooropvang vanaf 7:00)',
            $this->formatter->format($this->camp()->withChildcare(new Childcare('07:00', null)))
        );
    }

    public function testItShowsChildcareThatOnlyHappensAfterTheOpeningHours(): void
    {
        $this->assertEquals(
            'Donderdag 13 augustus 2026 van 08:00 tot 17:00 (naopvang tot 18:00)',
            $this->formatter->format($this->camp()->withChildcare(new Childcare(null, '18:00')))
        );
    }

    public function testItShowsTheOvernightStayWithoutChildcare(): void
    {
        $this->assertEquals(
            'Donderdag 13 augustus 2026 van 08:00 tot 17:00 (met overnachting)',
            $this->formatter->format($this->camp()->withOvernight(true))
        );
    }

    public function testItCombinesTheOvernightStayAndTheChildcare(): void
    {
        $this->assertEquals(
            'Donderdag 13 augustus 2026 van 08:00 tot 17:00 (met overnachting, opvang van 7:00 tot 18:00)',
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
            'Donderdag 13 augustus 2026 van 08:00 tot 17:00 '
            . '(met overnachting, opvang van 7:00 tot 18:00) (geannuleerd)',
            $this->formatter->format($event)
        );
    }

    public function testItTranslatesTheChildcareAndTheOvernightStay(): void
    {
        $this->assertEquals(
            'Jeudi 13 août 2026 de 08:00 à 17:00 (avec nuitée, garderie de 7:00 à 18:00)',
            (new LargeSinglePlainTextFormatter(new Translator('fr')))->format($this->campWithChildcareAndOvernight())
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
