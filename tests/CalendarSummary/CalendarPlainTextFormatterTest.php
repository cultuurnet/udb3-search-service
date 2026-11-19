<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\ExtraSmallMultiplePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\LargeMultiplePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\MediumMultiplePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\SmallMultiplePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\AdjustedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\ClosedDay;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\ExtraLargePeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\ExtraSmallPeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\LargePeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\MediumPeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\SmallPeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\ExtraLargePermanentPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\LargePermanentPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\MediumPermanentPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\ExtraSmallSinglePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\LargeSinglePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\MediumSinglePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\SmallSinglePlainTextFormatter;
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
        Chronos::setTestNow(new Chronos('2021-05-03'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
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
        $this->expectExceptionMessage('sx format not supported for single');
        $this->formatter->format($offer, 'sx');
    }

    /**
     * @dataProvider providesRoutes
     */
    public function testEverySizeRoutesToItsFormatter(Offer $offer, string $size, callable $expectedFormatter): void
    {
        $translator = new Translator('nl_BE');

        $this->assertSame(
            $expectedFormatter($translator)->format($offer),
            $this->formatter->format($offer, $size)
        );
    }

    public function providesRoutes(): array
    {
        $single = $this->single();
        $multiple = $this->multiple();
        $periodic = $this->periodic();
        $permanent = $this->permanent();

        return [
            'single xl' => [$single, 'xl', fn (Translator $t) => new LargeSinglePlainTextFormatter($t)],
            'single lg' => [$single, 'lg', fn (Translator $t) => new LargeSinglePlainTextFormatter($t)],
            'single md' => [$single, 'md', fn (Translator $t) => new MediumSinglePlainTextFormatter($t)],
            'single sm' => [$single, 'sm', fn (Translator $t) => new SmallSinglePlainTextFormatter($t)],
            'single xs' => [$single, 'xs', fn (Translator $t) => new ExtraSmallSinglePlainTextFormatter($t)],
            'multiple xl' => [$multiple, 'xl', fn (Translator $t) => new LargeMultiplePlainTextFormatter($t, false)],
            'multiple lg' => [$multiple, 'lg', fn (Translator $t) => new LargeMultiplePlainTextFormatter($t, false)],
            'multiple md' => [$multiple, 'md', fn (Translator $t) => new MediumMultiplePlainTextFormatter($t, false)],
            'multiple sm' => [$multiple, 'sm', fn (Translator $t) => new SmallMultiplePlainTextFormatter($t)],
            'multiple xs' => [$multiple, 'xs', fn (Translator $t) => new ExtraSmallMultiplePlainTextFormatter($t)],
            'periodic xl' => [$periodic, 'xl', fn (Translator $t) => new ExtraLargePeriodicPlainTextFormatter($t)],
            'periodic lg' => [$periodic, 'lg', fn (Translator $t) => new LargePeriodicPlainTextFormatter($t)],
            'periodic md' => [$periodic, 'md', fn (Translator $t) => new MediumPeriodicPlainTextFormatter($t)],
            'periodic sm' => [$periodic, 'sm', fn (Translator $t) => new SmallPeriodicPlainTextFormatter($t)],
            'periodic xs' => [$periodic, 'xs', fn (Translator $t) => new ExtraSmallPeriodicPlainTextFormatter($t)],
            'permanent xl' => [$permanent, 'xl', fn (Translator $t) => new ExtraLargePermanentPlainTextFormatter($t)],
            'permanent lg' => [$permanent, 'lg', fn (Translator $t) => new LargePermanentPlainTextFormatter($t)],
            'permanent md' => [$permanent, 'md', fn (Translator $t) => new MediumPermanentPlainTextFormatter($t)],
            'permanent sm' => [$permanent, 'sm', fn (Translator $t) => new MediumPermanentPlainTextFormatter($t)],
            'permanent xs' => [$permanent, 'xs', fn (Translator $t) => new MediumPermanentPlainTextFormatter($t)],
        ];
    }

    public function testItFormatsInTheGivenLanguage(): void
    {
        $this->assertSame(
            (new LargeSinglePlainTextFormatter(new Translator('fr')))->format($this->single()),
            (new CalendarPlainTextFormatter('fr'))->format($this->single(), 'lg')
        );
    }

    public function testItHidesPastDatesWhenAsked(): void
    {
        $multiple = $this->multiple();

        $this->assertSame(
            (new LargeMultiplePlainTextFormatter(new Translator('nl_BE'), true))->format($multiple),
            (new CalendarPlainTextFormatter('nl_BE', true))->format($multiple, 'lg')
        );
        $this->assertNotSame(
            (new CalendarPlainTextFormatter('nl_BE', true))->format($multiple, 'lg'),
            (new CalendarPlainTextFormatter('nl_BE', false))->format($multiple, 'lg')
        );
    }

    public function testItFormatsInTheGivenTimeZone(): void
    {
        $previousTimeZone = date_default_timezone_get();
        $event = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2030-01-25T23:30:00+00:00'),
            new DateTimeImmutable('2030-01-25T23:45:00+00:00'),
            CalendarType::single()
        );

        try {
            $this->assertStringContainsString('23:30', (new CalendarPlainTextFormatter('nl_BE', false, 'UTC'))->format($event, 'lg'));
            $this->assertStringContainsString('00:30', (new CalendarPlainTextFormatter('nl_BE', false, 'Europe/Brussels'))->format($event, 'lg'));
        } finally {
            date_default_timezone_set($previousTimeZone);
        }
    }

    private function single(): Offer
    {
        return new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2030-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2030-01-25T21:30:00+01:00'),
            CalendarType::single()
        );
    }

    private function multiple(): Offer
    {
        return (new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2030-02-01T21:30:00+01:00'),
            CalendarType::multiple()
        ))->withSubEvents(
            [
                new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Available'),
                    new DateTimeImmutable('2021-01-25T20:00:00+01:00'),
                    new DateTimeImmutable('2021-01-25T21:30:00+01:00')
                ),
                new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Available'),
                    new DateTimeImmutable('2030-02-01T20:00:00+01:00'),
                    new DateTimeImmutable('2030-02-01T21:30:00+01:00')
                ),
            ]
        );
    }

    private function periodic(): Offer
    {
        return (new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2030-01-01T00:00:00+01:00'),
            new DateTimeImmutable('2030-12-31T23:59:59+01:00'),
            CalendarType::periodic()
        ))->withOpeningHours([new OpeningHour(['monday', 'tuesday'], '10:00', '18:00')]);
    }

    private function permanent(): Offer
    {
        return (new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        ))->withOpeningHours([new OpeningHour(['monday', 'tuesday'], '10:00', '18:00')]);
    }
}
