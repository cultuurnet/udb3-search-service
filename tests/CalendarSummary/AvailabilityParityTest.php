<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OfferType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AvailabilityParityTest extends TestCase
{
    protected function setUp(): void
    {
        Chronos::setTestNow(new Chronos('2021-05-03'));
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow();
    }

    /**
     * @dataProvider providesUnavailableOffers
     */
    public function testHtmlAndPlainTextBothMentionTheAvailability(
        OfferType $offerType,
        string $calendarType,
        string $status,
        string $bookingAvailability,
        string $size,
        string $expected
    ): void {
        $offer = $this->offer($offerType, new CalendarType($calendarType), $status, $bookingAvailability);

        // A summary that consists of the availability alone starts with a capital, one that closes a date
        // does not, so only the wording is compared.
        $this->assertStringContainsStringIgnoringCase(
            $expected,
            (new CalendarPlainTextFormatter())->format($offer, $size)
        );
        $this->assertStringContainsStringIgnoringCase($expected, (new CalendarHTMLFormatter())->format($offer, $size));
    }

    public function providesUnavailableOffers(): array
    {
        $eventAvailabilities = [
            'cancelled' => ['Unavailable', 'Available', 'geannuleerd'],
            'postponed' => ['TemporarilyUnavailable', 'Available', 'uitgesteld'],
            'sold out' => ['Available', 'Unavailable', 'Volzet of uitverkocht'],
        ];

        $placeAvailabilities = [
            'permanently closed' => ['Unavailable', 'Available', 'Permanent gesloten'],
            'temporarily closed' => ['TemporarilyUnavailable', 'Available', 'Tijdelijk gesloten'],
            'sold out' => ['Available', 'Unavailable', 'Volzet of uitverkocht'],
        ];

        $offers = [
            'single' => [OfferType::event(), $eventAvailabilities],
            'multiple' => [OfferType::event(), $eventAvailabilities],
            'periodic' => [OfferType::event(), $eventAvailabilities],
            'permanent' => [OfferType::event(), $eventAvailabilities],
            'permanent place' => [OfferType::place(), $placeAvailabilities],
        ];

        $cases = [];
        foreach ($offers as $offerName => [$offerType, $availabilities]) {
            $calendarType = explode(' ', $offerName)[0];
            foreach ($availabilities as $name => [$status, $bookingAvailability, $expected]) {
                foreach (['xs', 'sm', 'md', 'lg', 'xl'] as $size) {
                    $cases["$offerName $name $size"] = [
                        $offerType,
                        $calendarType,
                        $status,
                        $bookingAvailability,
                        $size,
                        $expected,
                    ];
                }
            }
        }
        return $cases;
    }

    private function offer(
        OfferType $offerType,
        CalendarType $calendarType,
        string $status,
        string $bookingAvailability
    ): Offer {
        $event = new Offer(
            $offerType,
            new Status($status, []),
            new BookingAvailability($bookingAvailability),
            new DateTimeImmutable('2030-01-25T20:00:00+01:00'),
            new DateTimeImmutable('2030-01-25T21:30:00+01:00'),
            $calendarType
        );

        return $event->withSubEvents([
            new Offer(
                OfferType::event(),
                new Status($status, []),
                new BookingAvailability($bookingAvailability),
                new DateTimeImmutable('2030-01-25T20:00:00+01:00'),
                new DateTimeImmutable('2030-01-25T21:30:00+01:00')
            ),
        ]);
    }
}
