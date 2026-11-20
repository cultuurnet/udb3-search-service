<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\BookingAvailability;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Status;

final class HtmlAvailabilityFormatter
{
    /**
     * @var bool
     */
    private $isAvailable;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var BookingAvailability
     */
    private $bookingAvailability;

    /**
     * @var string
     */
    private $element = 'span';

    /**
     * @var bool
     */
    private $withBraces = false;

    /**
     * @var bool
     */
    private $capitalize = false;

    /**
     * @var bool
     */
    private $isPlace = false;

    private function __construct(private Translator $translator)
    {
    }

    public static function forOffer(Offer $offer, Translator $translator): self
    {
        $formatter = new self($translator);
        $formatter->isAvailable = $offer->isAvailable();
        $formatter->status = $offer->getStatus();
        $formatter->bookingAvailability = $offer->getBookingAvailability();
        $formatter->isPlace = $offer->isPlace();
        return $formatter;
    }

    public function withBraces(): self
    {
        $c = clone $this;
        $c->withBraces = true;
        return $c;
    }

    public function withoutBraces(): self
    {
        $c = clone $this;
        $c->withBraces = false;
        return $c;
    }

    public function withElement(string $element): self
    {
        $c = clone $this;
        $c->element = $element;
        return $c;
    }

    public function capitalize(): self
    {
        $c = clone $this;
        $c->capitalize = true;
        return $c;
    }

    public function toString(): string
    {
        if ($this->isAvailable) {
            return '';
        }

        $openTag = '<' . $this->element . ' ' . $this->getReasonAsTitleAttribute() . 'class="cf-status">';
        $closingTag = '</' . $this->element . '>';
        $availabilityText = $this->getAvailabilityText();

        if ($this->capitalize) {
            $availabilityText = ucfirst($availabilityText);
        }

        if ($this->withBraces) {
            $availabilityText = '(' . $availabilityText . ')';
        }

        return $openTag . $availabilityText . $closingTag;
    }

    private function getAvailabilityText(): string
    {
        if ($this->isPlace) {
            return $this->status->getType() === 'Unavailable' ?
                $this->translator->translate('permanently_closed') :
                $this->translator->translate('temporarily_closed');
        }

        if ($this->status->getType() !== 'Available') {
            return $this->status->getType() === 'Unavailable' ?
                $this->translator->translate('cancelled') :
                $this->translator->translate('postponed');
        }

        if (!$this->bookingAvailability->isAvailable()) {
            return $this->translator->translate('sold_out');
        }

        return '';
    }

    private function getReasonAsTitleAttribute(): string
    {
        $translatedReason = $this->status->getReasonForLanguage($this->translator->getLanguageCode());

        if (empty($translatedReason)) {
            return '';
        }

        return 'title="' . $translatedReason . '" ';
    }
}
