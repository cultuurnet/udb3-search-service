<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use Carbon\CarbonImmutable;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarSummaryTester;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SmallMultiplePlainTextFormatterTest extends TestCase
{
    use PlainTextFixture;

    /**
     * @var SmallMultiplePlainTextFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        $this->formatter = new SmallMultiplePlainTextFormatter(new Translator('nl_NL'));
        CalendarSummaryTester::setTestNow(2021, 5, 3);
    }

    public function testFormatMultipleWithoutLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            $this->expectedText('multiple-without-leading-zeroes'),
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleOnSamedayToday(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 3)->setTime(11, 30),
            CarbonImmutable::create(2021, 5, 3)->setTime(20, 30),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Vandaag',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleOnSamedayTonight(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 3)->setTime(18, 0),
            CarbonImmutable::create(2021, 5, 3)->setTime(21, 30),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Vanavond',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleSingleDateSmTomorrow(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 4)->setTime(18, 0),
            CarbonImmutable::create(2021, 5, 4)->setTime(21, 30),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Morgen',
            $this->formatter->format($event)
        );
    }

    public function testFormatMultipleCurrentWeek(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            CarbonImmutable::create(2021, 5, 9)->setTime(18, 0),
            CarbonImmutable::create(2021, 5, 9)->setTime(21, 30),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Deze zondag',
            $this->formatter->format($event)
        );
    }

    public function testFormatMultipleWithLeadingZeroes(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-03-2025'),
            new DateTimeImmutable('08-03-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Di 4 mrt 2025 - vr 8 mrt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-03-2021'),
            new DateTimeImmutable('08-03-2021'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Do 4 mrt - ma 8 mrt',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleStartsCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-03-2021'),
            new DateTimeImmutable('08-03-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Do 4 mrt - vr 8 mrt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleEndsCurrentYear(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-03-2020'),
            new DateTimeImmutable('08-03-2021'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Wo 4 mrt 2020 - ma 8 mrt',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('30-11-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Di 25 nov 2025 - za 30 nov 2030 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleDayWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-03-2025'),
            new DateTimeImmutable('30-03-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Di 25 mrt 2025 - za 30 mrt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleMonthWithoutLeadingZero(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('04-10-2025'),
            new DateTimeImmutable('08-10-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Za 4 okt 2025 - di 8 okt 2030',
            $this->formatter->format($offer)
        );
    }

    public function testFormatMultipleDayWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-03-2025'),
            new DateTimeImmutable('30-03-2030'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Di 25 mrt 2025 - za 30 mrt 2030 (geannuleerd)',
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
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Wo 8 okt 2025',
            $this->formatter->format($offer)
        );
    }

    public function testFormatAPeriodWithSameBeginAndEndDateWithUnavailableStatus(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('08-10-2025'),
            new DateTimeImmutable('08-10-2025'),
            CalendarType::multiple()
        );

        $this->assertEquals(
            'Wo 8 okt 2025 (geannuleerd)',
            $this->formatter->format($offer)
        );
    }
}
