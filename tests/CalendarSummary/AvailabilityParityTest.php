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
     * @dataProvider providesUnavailableEvents
     */
    public function testHtmlAndPlainTextBothMentionTheAvailability(
        string $calendarType,
        string $status,
        string $bookingAvailability,
        string $size,
        string $expected
    ): void {
        $event = $this->event(new CalendarType($calendarType), $status, $bookingAvailability);

        $this->assertStringContainsString($expected, (new CalendarPlainTextFormatter())->format($event, $size));
        $this->assertStringContainsString($expected, (new CalendarHTMLFormatter())->format($event, $size));
    }

    public function providesUnavailableEvents(): array
    {
        $availabilities = [
            'cancelled' => ['Unavailable', 'Available', 'geannuleerd'],
            'postponed' => ['TemporarilyUnavailable', 'Available', 'uitgesteld'],
            'sold out' => ['Available', 'Unavailable', 'Volzet of uitverkocht'],
        ];

        $cases = [];
        foreach (['single', 'multiple', 'periodic'] as $calendarType) {
            foreach ($availabilities as $name => [$status, $bookingAvailability, $expected]) {
                foreach (['xs', 'sm', 'md', 'lg', 'xl'] as $size) {
                    $cases["$calendarType $name $size"] = [$calendarType, $status, $bookingAvailability, $size, $expected];
                }
            }
        }
        return $cases;
    }

    private function event(CalendarType $calendarType, string $status, string $bookingAvailability): Offer
    {
        $event = new Offer(
            OfferType::event(),
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
