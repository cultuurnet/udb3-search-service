<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Offer;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;

final class Offer
{
    /**
     * @var OfferType
     */
    private $offerType;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var BookingAvailability
     */
    private $bookingAvailability;

    /**
     * @var DateTimeImmutable|null
     */
    private $startDate;

    /**
     * @var DateTimeImmutable|null
     */
    private $endDate;

    /**
     * @var CalendarType|null
     */
    private $calendarType;

    /**
     * @var Offer[]
     */
    private $subEvents = [];

    /**
     * @var OpeningHours
     */
    private $openingHours;

    /**
     * @var AdjustedDay[]
     */
    private $adjustedDays = [];

    /**
     * @var ClosedDay[]
     */
    private $closedDays = [];

    /**
     * @var Childcare|null
     */
    private $childcare;

    /**
     * @var bool
     */
    private $overnight = false;

    public function __construct(
        OfferType $offerType,
        Status $status,
        BookingAvailability $bookingAvailability,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        ?CalendarType $calendarType = null
    ) {
        $this->offerType = $offerType;
        $this->status = $status;
        $this->bookingAvailability = $bookingAvailability;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->calendarType = $calendarType;
        $this->openingHours = new OpeningHours();
    }

    public static function fromJsonLd(string $json): self
    {
        $data = json_decode($json, true);

        $offer = new self(
            OfferType::fromContext(strtolower($data['@context'])),
            Status::fromArray($data['status']),
            BookingAvailability::fromArray($data['bookingAvailability']),
            isset($data['startDate']) ? new DateTimeImmutable($data['startDate']) : null,
            isset($data['endDate']) ? new DateTimeImmutable($data['endDate']) : null,
            new CalendarType($data['calendarType'])
        );

        if (isset($data['subEvent'])) {
            $offer = $offer->withSubEvents(self::parseSubEvents($data['subEvent']));

            // A single event repeats its only sub-event, but the childcare and overnight
            // are only stored on that sub-event while the formatters get the offer itself.
            if ($data['calendarType'] === CalendarType::single()->toString() && isset($offer->subEvents[0])) {
                $offer = $offer
                    ->withChildcare($offer->subEvents[0]->childcare)
                    ->withOvernight($offer->subEvents[0]->overnight);
            }
        }

        if (isset($data['openingHours'])) {
            $offer = $offer->withOpeningHours(self::parseOpeningHours($data['openingHours']));
        }

        if (isset($data['openingHoursAdjustedDays'])) {
            $offer = $offer->withAdjustedDays(
                self::parseAdjustedDays($data['openingHoursAdjustedDays'])
            );
        }

        if (isset($data['openingHoursClosedDays'])) {
            $offer = $offer->withClosedDays(
                self::parseClosedDays($data['openingHoursClosedDays'])
            );
        }

        return $offer;
    }

    /**
     * @return Offer[]
     */
    private static function parseSubEvents(array $data): array
    {
        $subEvents = [];
        foreach ($data as $subEventData) {
            $subEvent = new self(
                OfferType::event(),
                Status::fromArray($subEventData['status']),
                BookingAvailability::fromArray($subEventData['bookingAvailability']),
                new DateTimeImmutable($subEventData['startDate']),
                new DateTimeImmutable($subEventData['endDate'])
            );

            $subEvents[] = $subEvent
                ->withChildcare(Childcare::fromArrayOrNull($subEventData['childcare'] ?? null))
                ->withOvernight((bool) ($subEventData['overnight'] ?? false));
        }

        return $subEvents;
    }

    /**
     * @return OpeningHour[]
     */
    private static function parseOpeningHours(array $data): array
    {
        $openingHours = [];
        foreach ($data as $openingHourData) {
            $openingHours[] = OpeningHour::fromArray($openingHourData);
        }

        return $openingHours;
    }

    /**
     * @return AdjustedDay[]
     */
    private static function parseAdjustedDays(array $data): array
    {
        $adjustedDays = [];
        foreach ($data as $adjustedDayData) {
            $adjustedDays[] = AdjustedDay::fromArray($adjustedDayData);
        }

        return $adjustedDays;
    }

    /**
     * @return ClosedDay[]
     */
    private static function parseClosedDays(array $data): array
    {
        $closedDays = [];
        foreach ($data as $closedDayData) {
            $closedDays[] = ClosedDay::fromArray($closedDayData);
        }

        return $closedDays;
    }

    /**
     * @param Offer[] $subEvents
     */
    public function withSubEvents(array $subEvents): self
    {
        $clone = clone $this;
        $clone->subEvents = $subEvents;

        return $clone;
    }

    /**
     * @param OpeningHour[] $openingHours
     */
    public function withOpeningHours(array $openingHours): self
    {
        $clone = clone $this;
        $clone->openingHours = (new OpeningHours($openingHours))
            ->splitPerDay()
            ->sortedByDayAndOpeningTime();

        return $clone;
    }

    /**
     * @param AdjustedDay[] $adjustedDays
     */
    public function withAdjustedDays(array $adjustedDays): self
    {
        $clone = clone $this;
        $clone->adjustedDays = $adjustedDays;

        return $clone;
    }

    /**
     * @param ClosedDay[] $closedDays
     */
    public function withClosedDays(array $closedDays): self
    {
        $clone = clone $this;
        $clone->closedDays = $closedDays;

        return $clone;
    }

    public function withChildcare(?Childcare $childcare): self
    {
        $clone = clone $this;
        $clone->childcare = $childcare;

        return $clone;
    }

    public function withOvernight(bool $overnight): self
    {
        $clone = clone $this;
        $clone->overnight = $overnight;

        return $clone;
    }

    public function withAvailability(Status $status, BookingAvailability $bookingAvailability): self
    {
        $clone = clone $this;

        $clone->status = $status;
        $clone->bookingAvailability = $bookingAvailability;

        return $clone;
    }

    public function getCalendarType(): CalendarType
    {
        if ($this->calendarType === null) {
            throw new LogicException('Offer has no calendar type');
        }

        return $this->calendarType;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public function isAvailable(): bool
    {
        return $this->status->getType() === 'Available' && $this->bookingAvailability->isAvailable();
    }

    /**
     * Only a permanent offer has no dates, and its formatters never ask for them.
     */
    public function getStartDate(): DateTimeImmutable
    {
        if ($this->startDate === null) {
            throw new LogicException('Offer has no start date');
        }

        return $this->startDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    public function getEndDate(): DateTimeImmutable
    {
        if ($this->endDate === null) {
            throw new LogicException('Offer has no end date');
        }

        return $this->endDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
    }

    public function isEvent(): bool
    {
        return $this->offerType->equals(OfferType::event());
    }

    public function isPlace(): bool
    {
        return $this->offerType->equals(OfferType::place());
    }

    /**
     * @return Offer[]
     */
    public function getSubEvents(): array
    {
        return $this->subEvents;
    }

    public function getOpeningHours(): OpeningHours
    {
        return $this->openingHours;
    }

    /**
     * @return AdjustedDay[]
     */
    public function getAdjustedDays(): array
    {
        return $this->adjustedDays;
    }

    /**
     * @return ClosedDay[]
     */
    public function getClosedDays(): array
    {
        return $this->closedDays;
    }

    public function getChildcare(): ?Childcare
    {
        return $this->childcare;
    }

    public function hasOvernight(): bool
    {
        return $this->overnight;
    }
}
