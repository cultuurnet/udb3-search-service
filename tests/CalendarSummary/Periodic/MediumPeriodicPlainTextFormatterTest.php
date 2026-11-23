<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class MediumPeriodicPlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var MediumPeriodicPlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new MediumPeriodicPlainTextFormatter(new Translator('nl_NL'));
    }

    public function testFormatAPeriodWithoutLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            $this->expectedText('period-without-leading-zeroes'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-03-2025'),
            new DateTimeImmutable('08-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Van di 4 maart 2025 tot vr 8 maart 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Van di 25 november 2025 tot za 30 november 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodDayWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-03-2025'),
            new DateTimeImmutable('30-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Van di 25 maart 2025 tot za 30 maart 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodMonthWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-10-2025'),
            new DateTimeImmutable('08-10-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Van za 4 oktober 2025 tot di 8 oktober 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodDayWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-03-2025'),
            new DateTimeImmutable('30-03-2030'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Van di 25 maart 2025 tot za 30 maart 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithSameBeginAndEndDate(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('08-10-2025'),
            new DateTimeImmutable('08-10-2025'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Wo 8 oktober 2025',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithSameBeginAndEndDatWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('08-10-2025'),
            new DateTimeImmutable('08-10-2025'),
            CalendarType::periodic()
        );

        $this->assertEquals(
            'Wo 8 oktober 2025 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }
}
