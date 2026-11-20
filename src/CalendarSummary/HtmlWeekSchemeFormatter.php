<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use DateTimeImmutable;

/**
 * Renders the opening hours of every day of the week as a list.
 */
final class HtmlWeekSchemeFormatter
{
    private DateFormatter $formatter;

    private Translator $translator;

    private OpeningHours $openingHours;

    private bool $withChildcare = false;

    private bool $childcareInNestedList = false;

    private bool $withAdjustedHoursNotice = false;

    private bool $withEveryDayOfTheWeek = false;

    private bool $sortInWeekOrder = false;

    private bool $withHeading = false;

    private function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    public static function forOpeningHours(OpeningHours $openingHours, Translator $translator): self
    {
        $formatter = new self($translator);
        $formatter->openingHours = $openingHours;
        return $formatter;
    }

    /**
     * Mentions the childcare of the opening hours.
     */
    public function withChildcare(): self
    {
        $c = clone $this;
        $c->withChildcare = true;
        return $c;
    }

    /**
     * Mentions the childcare of the opening hours as a list nested inside the day it belongs to.
     */
    public function withChildcareInNestedList(): self
    {
        $c = clone $this;
        $c->withChildcare = true;
        $c->childcareInNestedList = true;
        return $c;
    }

    /**
     * Warns that the listed hours do not hold during the adjusted days, which are not
     * listed themselves.
     */
    public function withAdjustedHoursNotice(): self
    {
        $c = clone $this;
        $c->withAdjustedHoursNotice = true;
        return $c;
    }

    /**
     * Lists every day of the week in week order, marking the days without opening hours as closed.
     */
    public function withEveryDayOfTheWeek(): self
    {
        $c = clone $this;
        $c->withEveryDayOfTheWeek = true;
        $c->sortInWeekOrder = true;
        return $c;
    }

    /**
     * Lists the days with opening hours in the order of the week, leaving out the days without.
     */
    public function inWeekOrder(): self
    {
        $c = clone $this;
        $c->sortInWeekOrder = true;
        return $c;
    }

    /**
     * Introduces the list with the 'open at' caption.
     */
    public function withHeading(): self
    {
        $c = clone $this;
        $c->withHeading = true;
        return $c;
    }

    public function toString(): string
    {
        // Without opening hours there is no week scheme to show. Saying nothing beats
        // an empty list or a week of closed days, which would claim the place is never
        // open while empty opening hours mean the opposite.
        if ($this->openingHours->isEmpty()) {
            return '';
        }

        // When every day has the same childcare it is summarized in a single list item
        // instead of being repeated on every day.
        $sharedChildcare = $this->withChildcare ? $this->openingHours->sharedChildcare() : null;

        $formattedDays = [];
        foreach ($this->openingHours as $openingHour) {
            $daysOfWeek = $openingHour->getDaysOfWeek();
            $opens = OpeningHourFormatter::format($openingHour->getOpens());
            $closes = OpeningHourFormatter::format($openingHour->getCloses());

            foreach ($daysOfWeek as $dayOfWeek) {
                if (!isset($formattedDays[$dayOfWeek])) {
                    $formattedDays[$dayOfWeek] = $this->openDay($dayOfWeek, $daysOfWeek)
                        . $this->generateTimespan($opens, $closes);
                } else {
                    $formattedDays[$dayOfWeek] .= $this->generateTimespan($opens, $closes, true);
                }
            }
        }

        // The childcare of a day follows its last timespan, so the timespans of a day that
        // opens more than once stay together on one line.
        if ($sharedChildcare === null) {
            foreach ($formattedDays as $dayOfWeek => $formattedDay) {
                $formattedDays[$dayOfWeek] .= $this->generateChildcare($this->childcareOfDay($dayOfWeek));
            }
        }

        $output = $this->withHeading
            ? '<p class="cf-openinghours">' . ucfirst($this->translator->translate('open_at')) . ':</p>'
            : '';
        $output .= '<ul class="list-unstyled">';

        foreach ($this->sortedDays($formattedDays) as $formattedDay) {
            $output .= $formattedDay . '</li>';
        }

        if ($sharedChildcare !== null) {
            $output .= '<li class="cf-childcare">'
                . ChildcareFormatter::forChildcare($sharedChildcare, $this->translator)
                    ->forEveryDay()
                    ->capitalize()
                    ->toString()
                . '</li>';
        }

        if ($this->withAdjustedHoursNotice) {
            $output .= '<li class="cf-adjusted-days">'
                . $this->translator->translate('adjusted_hours_notice')
                . '</li>';
        }

        return $output . '</ul>';
    }

    /**
     * The childcare of every timespan of a day, or the single one they have in common.
     *
     * @return Childcare[]
     */
    private function childcareOfDay(string $dayOfWeek): array
    {
        if (!$this->withChildcare) {
            return [];
        }

        $openingHours = $this->openingHours->onDayOfWeek($dayOfWeek);
        $shared = $openingHours->sharedChildcare();

        if ($shared !== null) {
            return [$shared];
        }

        $childcares = [];
        foreach ($openingHours as $openingHour) {
            $childcare = $openingHour->getChildcare();

            if ($childcare !== null) {
                $childcares[] = $childcare;
            }
        }

        return $childcares;
    }

    /**
     * Opens the list item of a day that has opening hours.
     *
     * @param string[] $daysOfWeek the days that share the timespans of this day
     */
    private function openDay(string $dayOfWeek, array $daysOfWeek): string
    {
        $firstOpens = OpeningHourFormatter::format($this->openingHours->earliestTimeOn($daysOfWeek));
        $lastCloses = OpeningHourFormatter::format($this->openingHours->latestTimeOn($daysOfWeek));
        $daySpanShort = ucfirst($this->formatter->formatAsAbbreviatedDayOfWeek(new DateTimeImmutable($dayOfWeek)));

        return "<meta itemprop=\"openingHours\" datetime=\"$daySpanShort $firstOpens-$lastCloses\"> "
            . '</meta> '
            . '<li itemprop="openingHoursSpecification"> '
            . '<span class="cf-days">' . $this->translateDayOfWeek($dayOfWeek) . '</span> ';
    }

    /**
     * Renders a single 'from ... till ...', prefixed with 'and' when the day already has one.
     */
    private function generateTimespan(string $opens, string $closes, bool $afterAnotherTimespan = false): string
    {
        $and = $afterAnotherTimespan ? $this->translator->translate('and') . ' ' : '';

        return "<span itemprop=\"opens\" content=\"$opens\" class=\"cf-from cf-meta\">"
            . $and . $this->translator->translate('from_hour') . '</span> '
            . "<span class=\"cf-time\">$opens</span> "
            . "<span itemprop=\"closes\" content=\"$closes\" class=\"cf-to cf-meta\">"
            . $this->translator->translate('till_hour') . '</span> '
            . "<span class=\"cf-time\">$closes</span>";
    }

    /**
     * @param Childcare[] $childcares
     */
    private function generateChildcare(array $childcares): string
    {
        if (!$this->withChildcare) {
            return '';
        }

        if ($childcares === []) {
            return '';
        }

        $childcareText = ChildcareFormatter::forChildcares($childcares, $this->translator)
            ->withBraces()
            ->capitalize()
            ->toString();

        if ($this->childcareInNestedList) {
            return ' <ul class="list-unstyled">'
                . '<li class="cf-childcare">' . $childcareText . '</li>'
                . '</ul>';
        }

        return ' <span class="cf-childcare">' . $childcareText . '</span>';
    }

    /**
     * @param string[] $formattedDays the rendered days, keyed by day of the week
     * @return string[]
     */
    private function sortedDays(array $formattedDays): array
    {
        if (!$this->sortInWeekOrder) {
            return $formattedDays;
        }

        $sortedDays = [];
        foreach (OpeningHour::ALLOWED_DAYS as $dayOfWeek) {
            if (isset($formattedDays[$dayOfWeek])) {
                $sortedDays[$dayOfWeek] = $formattedDays[$dayOfWeek];
            } elseif ($this->withEveryDayOfTheWeek) {
                $sortedDays[$dayOfWeek] = $this->closedDay($dayOfWeek);
            }
        }

        return $sortedDays;
    }

    private function closedDay(string $dayOfWeek): string
    {
        $translatedDay = $this->translateDayOfWeek($dayOfWeek);

        return "<meta itemprop=\"openingHours\" datetime=\"$translatedDay\"> "
            . '</meta> '
            . '<li itemprop="openingHoursSpecification"> '
            . "<span class=\"cf-days\">$translatedDay</span> "
            . '<span itemprop="closed" content="closed" class="cf-closed cf-meta">'
            . $this->translator->translate('closed') . '</span> ';
    }

    private function translateDayOfWeek(string $dayOfWeek): string
    {
        return ucfirst($this->formatter->formatDayOfWeekName($dayOfWeek));
    }
}
