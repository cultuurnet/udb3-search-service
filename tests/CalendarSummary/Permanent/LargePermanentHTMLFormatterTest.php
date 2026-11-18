<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use Carbon\CarbonImmutable;
use CultuurNet\UDB3\Search\CalendarSummary\CalendarSummaryTester;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlFixture;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\AdjustedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LargePermanentHTMLFormatterTest extends TestCase
{
    use HtmlFixture;

    /**
     * @var LargePermanentHTMLFormatter
     */
    protected $formatter;

    protected function setUp(): void
    {
        // Monday 10 August 2026, so the adjusted days in November 2026 are upcoming.
        CalendarSummaryTester::setTestNow(2026, 8, 10);
        $this->formatter = new LargePermanentHTMLFormatter(new Translator('nl_NL'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    public function testFormatASimplePermanent(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('25-11-2025')
        );

        $openingHours1 = new OpeningHour(
            ['monday','tuesday', 'wednesday'],
            '00:01',
            '13:00'
        );

        $openingHours2 = new OpeningHour(
            ['monday'],
            '14:00',
            '20:00'
        );

        $openingHours3 = new OpeningHour(
            ['friday'],
            '09:00',
            '13:00'
        );

        $openingHours4 = new OpeningHour(
            ['saturday', 'sunday'],
            '09:00',
            '19:00'
        );

        $place = $place->withOpeningHours(
            [
                $openingHours2,
                $openingHours1,
                $openingHours3,
                $openingHours4,
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('simple-permanent'),
            $this->formatter->format($place)
        );
    }

    public function testFormatASimplePermanentInFrench(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('25-11-2025')
        );

        $openingHours1 = new OpeningHour(
            ['monday'],
            '00:01',
            '13:00'
        );

        $openingHours2 = new OpeningHour(
            ['friday'],
            '09:00',
            '13:00'
        );

        $place = $place->withOpeningHours(
            [
                $openingHours1,
                $openingHours2,
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('simple-permanent-in-french'),
            (new LargePermanentHTMLFormatter(new Translator('fr')))->format($place)
        );
    }

    public function testFormatAMixedPermanent(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('25-11-2025')
        );

        $openingHours1 = new OpeningHour(
            ['monday','tuesday', 'wednesday'],
            '09:00',
            '13:00'
        );

        $openingHours2 = new OpeningHour(
            ['monday','tuesday', 'wednesday'],
            '17:00',
            '20:00'
        );

        $openingHours3 = new OpeningHour(
            ['friday', 'saturday'],
            '10:00',
            '15:00'
        );

        $openingHours4 = new OpeningHour(
            ['friday', 'saturday'],
            '18:00',
            '21:00'
        );

        $place = $place->withOpeningHours(
            [
                $openingHours1,
                $openingHours2,
                $openingHours3,
                $openingHours4,
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('mixed-permanent'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAComplexPermanent(): void
    {
        $place = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('25-11-2025')
        );

        $openingHours1 = new OpeningHour(
            ['monday','tuesday'],
            '09:30',
            '13:45'
        );

        $openingHours2 = new OpeningHour(
            ['monday'],
            '17:00',
            '20:00'
        );

        $openingHours3 = new OpeningHour(
            ['tuesday'],
            '18:00',
            '20:00'
        );

        $openingHours4 = new OpeningHour(
            ['tuesday'],
            '21:00',
            '23:00'
        );

        $openingHours5 = new OpeningHour(
            ['friday', 'saturday'],
            '10:00',
            '15:00'
        );

        $place = $place->withOpeningHours(
            [
                $openingHours1,
                $openingHours2,
                $openingHours3,
                $openingHours4,
                $openingHours5,
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('complex-permanent'),
            $this->formatter->format($place)
        );
    }

    public function testFormatAnUnavailablePermanent(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $this->assertEquals(
            '<p class="cf-status">Geannuleerd</p>',
            $this->formatter->format($event)
        );
    }

    public function testFormatATemporarilyUnavailablePermanent(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('TemporarilyUnavailable', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $this->assertEquals(
            '<p class="cf-status">Uitgesteld</p>',
            $this->formatter->format($event)
        );
    }


    public function testItRendersReasonAsTitleAttribute(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', ['nl' => 'Covid-19']),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $this->assertEquals(
            '<p title="Covid-19" class="cf-status">Geannuleerd</p>',
            $this->formatter->format($event)
        );
    }

    public function testItDoesNotRendersReasonWhenTranslationIsUnavailable(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Unavailable', ['fr' => 'Sacre bleu']),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $this->assertEquals(
            '<p class="cf-status">Geannuleerd</p>',
            $this->formatter->format($event)
        );
    }

    public function testFormatPermanentWithoutOpeningHours(): void
    {
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $this->assertEquals(
            '<p class="cf-openinghours">Alle dagen open</p>',
            $this->formatter->format($event)
        );
    }

    /**
     * A nested list is a block of its own, so the childcare of a day is listed after its
     * last timespan even when it differs per timespan, instead of splitting them up.
     */
    public function testItNestsTheChildcareInsideTheDayItBelongsTo(): void
    {
        $place = $this->availablePlace()->withOpeningHours(
            [
                new OpeningHour(['monday'], '09:00', '12:00', new Childcare('08:00', '13:00')),
                new OpeningHour(['monday'], '14:00', '18:00', new Childcare('13:00', '19:00')),
                new OpeningHour(['saturday'], '10:00', '18:00', new Childcare(null, '19:00')),
            ]
        );

        $this->assertEquals(
            $this->expectedHtml('permanent-with-childcare'),
            $this->formatter->format($place)
        );
    }

    /**
     * The large format does not list the adjusted days, so it only warns that they exist.
     */
    public function testItWarnsThatTheHoursCanDifferDuringTheAdjustedDays(): void
    {
        $place = $this->availablePlace()
            ->withOpeningHours([new OpeningHour(['monday'], '09:00', '16:00')])
            ->withAdjustedDays(
                [
                    new AdjustedDay(
                        new DateTimeImmutable('2026-11-02'),
                        new DateTimeImmutable('2026-11-07'),
                        new OpeningHours([new OpeningHour(['monday'], '10:00', '15:00')]),
                        ['nl' => 'Herfstvakantie']
                    ),
                ]
            );

        $this->assertEquals(
            $this->expectedHtml('permanent-with-adjusted-days-notice'),
            $this->formatter->format($place)
        );
    }

    /**
     * Without opening hours there is nothing the adjusted days could differ from.
     */
    public function testItDoesNotWarnAboutAdjustedDaysWithoutOpeningHours(): void
    {
        $place = $this->availablePlace()->withAdjustedDays(
            [
                new AdjustedDay(
                    new DateTimeImmutable('2026-11-02'),
                    new DateTimeImmutable('2026-11-07'),
                    new OpeningHours(),
                    ['nl' => 'Herfstvakantie']
                ),
            ]
        );

        $this->assertEquals(
            '<p class="cf-openinghours">Alle dagen open</p>',
            $this->formatter->format($place)
        );
    }

    private function availablePlace(): Offer
    {
        return new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('25-11-2025'),
            new DateTimeImmutable('25-11-2025'),
            CalendarType::permanent()
        );
    }
}
