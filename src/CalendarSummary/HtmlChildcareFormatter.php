<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

/**
 * Renders the childcare and the overnight stay of a (sub)event.
 */
final class HtmlChildcareFormatter
{
    private ChildcareFormatter $childcareFormatter;

    private bool $asNestedList = false;

    private function __construct(ChildcareFormatter $childcareFormatter)
    {
        $this->childcareFormatter = $childcareFormatter;
    }

    public static function forOffer(Offer $offer, Translator $translator): self
    {
        return new self(ChildcareFormatter::forOffer($offer, $translator));
    }

    /**
     * Renders the childcare as a list of its own, to nest inside the list item of the date.
     */
    public function asNestedList(): self
    {
        $c = clone $this;
        $c->asNestedList = true;
        return $c;
    }

    public function toString(): string
    {
        $childcareFormatter = $this->childcareFormatter;

        // A nested list is a block of its own and therefore starts a sentence of its own,
        // between braces. Inline it continues the sentence of the date it follows.
        if ($this->asNestedList) {
            $childcareFormatter = $childcareFormatter->capitalize()->withBraces();
        }

        $childcareText = $childcareFormatter->toString();

        if ($childcareText === '') {
            return '';
        }

        if ($this->asNestedList) {
            return '<ul class="list-unstyled">'
                . '<li class="cf-childcare">' . $childcareText . '</li>'
                . '</ul>';
        }

        return '<span class="cf-childcare">' . $childcareText . '</span>';
    }
}
