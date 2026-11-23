<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use LogicException;

final class ChildcareFormatter
{
    /**
     * @var Childcare[]
     */
    private array $childcares = [];

    private bool $overnight = false;

    private string $prefix = '';

    private bool $withBraces = false;

    private bool $capitalize = false;

    private function __construct(private Translator $translator)
    {
    }

    public static function forChildcare(Childcare $childcare, Translator $translator): self
    {
        return self::forChildcares([$childcare], $translator);
    }

    /**
     * Renders the childcare of a single day, which has one per timespan that it is open.
     * Consecutive childcares of the same kind share its wording, so a day that is open
     * twice reads as 'opvang van 7:00 tot 13:00 en van 16:00 tot 20:00'.
     *
     * @param Childcare[] $childcares
     */
    public static function forChildcares(array $childcares, Translator $translator): self
    {
        $formatter = new self($translator);
        $formatter->childcares = array_values($childcares);
        return $formatter;
    }

    /**
     * Combines the childcare of a (sub)event with its overnight stay. Renders nothing
     * when it has neither of them.
     */
    public static function forOffer(Offer $offer, Translator $translator): self
    {
        $childcare = $offer->getChildcare();

        $formatter = $childcare === null ? new self($translator) : self::forChildcare($childcare, $translator);
        $formatter->overnight = $offer->hasOvernight();

        // Only the overnight stay starts the sentence, so the childcare that follows it
        // keeps its lowercase.
        if ($formatter->overnight && $formatter->childcares !== []) {
            return $formatter->precededBy($translator->translate('overnight') . ',');
        }

        return $formatter;
    }

    /**
     * Prefixes the childcare with 'elke dag', used when every day has the same childcare.
     */
    public function forEveryDay(): self
    {
        return $this->precededBy($this->translator->translate('every_day'));
    }

    /**
     * Introduces the childcare with the text it belongs to, like the day(s) it applies to or
     * the overnight stay it shares its sentence with.
     */
    public function precededBy(string $text): self
    {
        $c = clone $this;
        $c->prefix = $text;
        return $c;
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

    public function capitalize(): self
    {
        $c = clone $this;
        $c->capitalize = true;
        return $c;
    }

    public function toString(): string
    {
        $childcareText = $this->getChildcareText();

        if ($childcareText === '') {
            return '';
        }

        if ($this->prefix !== '') {
            $childcareText = $this->prefix . ' ' . $childcareText;
        }

        if ($this->capitalize) {
            $childcareText = ucfirst($childcareText);
        }

        if ($this->withBraces) {
            $childcareText = '(' . $childcareText . ')';
        }

        return $childcareText;
    }

    private function getChildcareText(): string
    {
        // An overnight stay is the only thing left to report when there is no childcare.
        if ($this->childcares === []) {
            return $this->overnight ? $this->translator->translate('overnight') : '';
        }

        $and = $this->translator->translate('and');
        $text = '';
        $previousKind = '';

        foreach ($this->childcares as $childcare) {
            $kind = $this->kindOf($childcare);

            if ($text === '') {
                $text = $kind . ' ' . $this->hoursOf($childcare);
            } elseif ($kind === $previousKind) {
                // The same kind of childcare only needs its hours, not its wording again.
                $text .= ' ' . $and . ' ' . $this->hoursOf($childcare);
            } else {
                $text .= ' ' . $and . ' ' . $kind . ' ' . $this->hoursOf($childcare);
            }

            $previousKind = $kind;
        }

        return $text;
    }

    /**
     * Childcare that only happens before the opening hours has no end, childcare that only
     * happens after them has no start.
     */
    private function kindOf(Childcare $childcare): string
    {
        if ($childcare->getEnd() === null) {
            return $this->translator->translate('childcare_before');
        }

        if ($childcare->getStart() === null) {
            return $this->translator->translate('childcare_after');
        }

        return $this->translator->translate('childcare');
    }

    private function hoursOf(Childcare $childcare): string
    {
        $start = $childcare->getStart();
        $end = $childcare->getEnd();

        if ($start !== null && $end !== null) {
            return $this->translator->translate('from_hour')
                . ' ' . OpeningHourFormatter::format($start)
                . ' ' . $this->translator->translate('till_hour')
                . ' ' . OpeningHourFormatter::format($end);
        }

        if ($start !== null) {
            return $this->translator->translate('childcare_from')
                . ' ' . OpeningHourFormatter::format($start);
        }

        if ($end !== null) {
            return $this->translator->translate('childcare_till')
                . ' ' . OpeningHourFormatter::format($end);
        }

        throw new LogicException('Childcare needs at least a start or an end');
    }
}
