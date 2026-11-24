<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class OfferTest extends TestCase
{
    public function testCanCreateFromJsonLd(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/offer.json');
        $expected = new Offer(
            OfferType::event(),
            new Status(
                'TemporarilyUnavailable',
                [
                    'nl' => 'Uitgesteld',
                    'en' => 'Postponed',
                ]
            ),
            new BookingAvailability('Unavailable'),
            new DateTimeImmutable('2021-03-01T23:00:00+00:00'),
            new DateTimeImmutable('2021-03-28T22:59:59+00:00'),
            CalendarType::single()
        );

        $this->assertEquals($expected, Offer::fromJsonLd($jsonLd));
    }

    public function testCanParsePermanentOffer(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/permanent.json');
        $expected = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $this->assertEquals($expected, Offer::fromJsonLd($jsonLd));
    }

    public function testPermanentOfferHasNoStartDate(): void
    {
        $offer = Offer::fromJsonLd(FileReader::read(__DIR__ . '/data/permanent.json'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Offer has no start date');
        $offer->getStartDate();
    }

    public function testPermanentOfferHasNoEndDate(): void
    {
        $offer = Offer::fromJsonLd(FileReader::read(__DIR__ . '/data/permanent.json'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Offer has no end date');
        $offer->getEndDate();
    }

    public function testOfferWithoutCalendarTypeHasNoCalendarType(): void
    {
        $offer = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-03-01T23:00:00+00:00'),
            new DateTimeImmutable('2021-03-14T22:59:59+00:00')
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Offer has no calendar type');
        $offer->getCalendarType();
    }

    public function testCanParseSubEvents(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/offer-with-subevents.json');
        $expected = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-03-01T23:00:00+00:00'),
            new DateTimeImmutable('2021-03-28T22:59:59+00:00'),
            CalendarType::multiple()
        );

        $expected = $expected->withSubEvents(
            [
                new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Unavailable'),
                    new DateTimeImmutable('2021-03-01T23:00:00+00:00'),
                    new DateTimeImmutable('2021-03-14T22:59:59+00:00')
                ),
                new Offer(
                    OfferType::event(),
                    new Status('Available', []),
                    new BookingAvailability('Available'),
                    new DateTimeImmutable('2021-03-15T23:00:00+00:00'),
                    new DateTimeImmutable('2021-03-28T22:59:59+00:00')
                ),
            ]
        );

        $this->assertEquals($expected, Offer::fromJsonLd($jsonLd));
    }

    public function testCanParseTheChildcareAndTheOvernightStayOfSubEvents(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/offer-with-subevent-childcare.json');

        $subEvents = Offer::fromJsonLd($jsonLd)->getSubEvents();

        $this->assertEquals(new Childcare('07:00', '18:00'), $subEvents[0]->getChildcare());
        $this->assertFalse($subEvents[0]->hasOvernight());

        $this->assertNull($subEvents[1]->getChildcare());
        $this->assertTrue($subEvents[1]->hasOvernight());

        // Childcare without a start and without an end counts as no childcare at all.
        $this->assertNull($subEvents[2]->getChildcare());
        $this->assertFalse($subEvents[2]->hasOvernight());
    }

    /**
     * A single event repeats its only sub-event, which is where its childcare is stored.
     */
    public function testItTakesTheChildcareOfASingleEventFromItsOnlySubEvent(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/single-offer-with-subevent-childcare.json');

        $offer = Offer::fromJsonLd($jsonLd);

        $this->assertEquals(new Childcare('07:00', '21:00'), $offer->getChildcare());
        $this->assertTrue($offer->hasOvernight());
    }

    /**
     * The childcare of a multiple event differs per sub-event, so there is none to show
     * for the event as a whole.
     */
    public function testItDoesNotTakeTheChildcareOfASubEventForAMultipleEvent(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/offer-with-subevent-childcare.json');

        $offer = Offer::fromJsonLd($jsonLd);

        $this->assertNull($offer->getChildcare());
        $this->assertFalse($offer->hasOvernight());
    }

    public function testCanParseOpeningHours(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/offer-with-opening-hours.json');
        $expected = new Offer(
            OfferType::place(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            new DateTimeImmutable('2021-03-01T23:00:00+00:00'),
            new DateTimeImmutable('2021-03-28T22:59:59+00:00'),
            CalendarType::periodic()
        );

        $expected = $expected->withOpeningHours(
            [
                new OpeningHour(
                    [
                        'monday',
                        'friday',
                        'saturday',
                    ],
                    '08:00',
                    '10:00'
                ),
                new OpeningHour(
                    [
                        'wednesday',
                    ],
                    '20:00',
                    '21:00'
                ),
            ]
        );

        $this->assertEquals($expected, Offer::fromJsonLd($jsonLd));
    }

    public function testCanParseOpeningHoursAdjustedAndClosedDays(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/offer-with-adjusted-days.json');

        $expected = new Offer(
            OfferType::event(),
            new Status('Available', []),
            new BookingAvailability('Available'),
            null,
            null,
            CalendarType::permanent()
        );

        $expected = $expected
            ->withOpeningHours(
                [
                    new OpeningHour(
                        [
                            'monday',
                            'tuesday',
                            'wednesday',
                            'thursday',
                        ],
                        '08:00',
                        '17:00',
                        new Childcare('07:00', '18:00')
                    ),
                ]
            )
            ->withAdjustedDays(
                [
                    new AdjustedDay(
                        new DateTimeImmutable('2026-11-02'),
                        new DateTimeImmutable('2026-11-07'),
                        new OpeningHours([
                            new OpeningHour(
                                [
                                    'monday',
                                    'tuesday',
                                    'wednesday',
                                    'thursday',
                                ],
                                '09:00',
                                '16:00'
                            ),
                        ]),
                        ['nl' => 'Herfstvakantie']
                    ),
                ]
            )
            ->withClosedDays(
                [
                    new ClosedDay(
                        new DateTimeImmutable('2026-12-24'),
                        new DateTimeImmutable('2027-01-03'),
                        ['nl' => 'Kerstvakantie']
                    ),
                ]
            );

        $this->assertEquals($expected, Offer::fromJsonLd($jsonLd));
    }

    public function testHasNoAdjustedOrClosedDaysWhenTheyAreAbsent(): void
    {
        $jsonLd = FileReader::read(__DIR__ . '/data/offer-with-opening-hours.json');
        $offer = Offer::fromJsonLd($jsonLd);

        $this->assertEquals([], $offer->getAdjustedDays());
        $this->assertEquals([], $offer->getClosedDays());
    }

    public function testTheContextIsCaseInsensitive(): void
    {
        $offer = Offer::fromJsonLd($this->jsonLd(['@context' => '/Contexts/Place']));

        $this->assertTrue($offer->isPlace());
    }

    public function testASingleEventWithoutSubEventsHasNoChildcare(): void
    {
        $offer = Offer::fromJsonLd($this->jsonLd(['calendarType' => 'single', 'subEvent' => []]));

        $this->assertSame([], $offer->getSubEvents());
        $this->assertNull($offer->getChildcare());
    }

    public function testEmptyOpeningHoursAreEmpty(): void
    {
        $offer = Offer::fromJsonLd($this->jsonLd(['openingHours' => []]));

        $this->assertTrue($offer->getOpeningHours()->isEmpty());
    }

    public function testAdjustedAndClosedDaysNeedNoOpeningHoursOrDescription(): void
    {
        $offer = Offer::fromJsonLd($this->jsonLd([
            'openingHoursAdjustedDays' => [['startDate' => '2026-11-02', 'endDate' => '2026-11-02']],
            'openingHoursClosedDays' => [['startDate' => '2026-12-25', 'endDate' => '2026-12-25']],
        ]));

        $this->assertEquals(
            [new AdjustedDay(new DateTimeImmutable('2026-11-02'), new DateTimeImmutable('2026-11-02'))],
            $offer->getAdjustedDays()
        );
        $this->assertEquals(
            [new ClosedDay(new DateTimeImmutable('2026-12-25'), new DateTimeImmutable('2026-12-25'))],
            $offer->getClosedDays()
        );
    }

    /**
     * @dataProvider providesInvalidJsonLd
     */
    public function testItRejectsInvalidValues(array $overrides, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        Offer::fromJsonLd($this->jsonLd($overrides));
    }

    public function providesInvalidJsonLd(): array
    {
        return [
            'unknown context' => [['@context' => '/contexts/organizer'], 'Unknown context: /contexts/organizer'],
            'unknown calendar type' => [['calendarType' => 'weekly'], 'Invalid CalendarType: weekly'],
            'unknown status' => [['status' => ['type' => 'Gone']], 'Invalid status type: Gone'],
            'unknown booking availability' => [
                ['bookingAvailability' => ['type' => 'Gone']],
                'Invalid booking availability type: Gone',
            ],
            'unknown day of the week' => [
                ['openingHours' => [['dayOfWeek' => ['funday'], 'opens' => '10:00', 'closes' => '18:00']]],
                'Invalid day: funday',
            ],
            'day of the week that is not a string' => [
                ['openingHours' => [['dayOfWeek' => [null], 'opens' => '10:00', 'closes' => '18:00']]],
                'Invalid day: null',
            ],
        ];
    }

    public function testAnOfferTypeNamesItselfWhenInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid OfferType: organizer');

        new OfferType('organizer');
    }

    private function jsonLd(array $overrides): string
    {
        return Json::encode(array_merge(
            [
                '@context' => '/contexts/event',
                'status' => ['type' => 'Available'],
                'bookingAvailability' => ['type' => 'Available'],
                'calendarType' => 'permanent',
            ],
            $overrides
        ));
    }
}
