<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Childcare;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHour;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;

/**
 * Renders the opening hours of a week as plain text.
 */
final class PlainTextWeekSchemeFormatter
{
    private DateFormatter $formatter;

    private Translator $translator;

    private OpeningHours $openingHours;

    private bool $asSingleLine = false;

    private bool $withChildcare = false;

    private bool $withoutClosedDays = false;

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
     * Lists only the days that have opening hours, on a single line between braces.
     * Without it every day of the week gets its own line and the days without
     * opening hours are marked as closed.
     */
    public function asSingleLine(): self
    {
        $c = clone $this;
        $c->asSingleLine = true;
        return $c;
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
     * Leaves the days without opening hours out of the line per day, instead of marking
     * them as closed.
     */
    public function withoutClosedDays(): self
    {
        $c = clone $this;
        $c->withoutClosedDays = true;
        return $c;
    }

    public function toString(): string
    {
        // Without opening hours there is no week scheme to show. Saying nothing beats
        // an empty '()' or a week of closed days, which would claim the place is never
        // open while empty opening hours mean the opposite.
        if ($this->openingHours->isEmpty()) {
            return '';
        }

        // When every day has the same childcare it is summarized in a single line
        // instead of being repeated on every day.
        $sharedChildcare = $this->withChildcare ? $this->openingHours->sharedChildcare() : null;

        $timespansPerDay = $this->timespansPerDay();

        $lines = $this->asSingleLine
            ? $this->formatAsSingleLine($timespansPerDay, $sharedChildcare)
            : $this->formatAsALinePerDay($timespansPerDay, $sharedChildcare);

        if ($sharedChildcare !== null) {
            $lines[] = ' ' . ChildcareFormatter::forChildcare($sharedChildcare, $this->translator)
                ->forEveryDay()
                ->withBraces()
                ->toString();
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @param array<string, OpeningHour[]> $timespansPerDay
     * @return string[]
     */
    private function formatAsSingleLine(array $timespansPerDay, ?Childcare $sharedChildcare): array
    {
        $formattedDays = [];
        foreach ($timespansPerDay as $dayOfWeek => $timespans) {
            $day = $this->formatDay($dayOfWeek, $timespans);

            // The days follow the opening brace instead of starting a sentence, so they lose
            // their capital in the languages that only capitalize them at the start of one.
            if (!$this->translator->capitalizesDaysOfWeek()) {
                $day = $day->lowercaseNextFirstCharacter();
            }

            $formattedDays[] = $day->toString();
        }

        $lines = ['(' . implode(', ', $formattedDays) . ')'];

        // The days share a single line, so their childcare cannot follow them
        // directly and needs to mention the day it applies to.
        if ($sharedChildcare === null) {
            foreach ($timespansPerDay as $dayOfWeek => $timespans) {
                $lines = array_merge($lines, $this->childcareLines($dayOfWeek, $timespans, true));
            }
        }

        return $lines;
    }

    /**
     * @param array<string, OpeningHour[]> $timespansPerDay
     * @return string[]
     */
    private function formatAsALinePerDay(array $timespansPerDay, ?Childcare $sharedChildcare): array
    {
        $lines = [];

        foreach (OpeningHour::ALLOWED_DAYS as $dayOfWeek) {
            $timespans = $timespansPerDay[$dayOfWeek] ?? [];

            if (!$timespans && $this->withoutClosedDays) {
                continue;
            }

            $lines[] = $this->formatDay($dayOfWeek, $timespans)->toString();

            if ($sharedChildcare === null) {
                $lines = array_merge($lines, $this->childcareLines($dayOfWeek, $timespans, false));
            }
        }

        return $lines;
    }

    /**
     * @param OpeningHour[] $timespans
     */
    private function formatDay(string $dayOfWeek, array $timespans): PlainTextSummaryBuilder
    {
        $day = PlainTextSummaryBuilder::start($this->translator)
            ->append($this->translateDayOfWeek($dayOfWeek));

        if (!$timespans) {
            return $day->closed();
        }

        $isFirstTimespan = true;
        foreach ($timespans as $timespan) {
            // fromHour() only inserts the 'and' by itself when 'from' and 'from_hour' translate to
            // the same word, which is not the case in French ('du' versus 'de').
            if (!$isFirstTimespan) {
                $day = $day->and();
            }
            $isFirstTimespan = false;

            $day = $day
                ->fromHour(OpeningHourFormatter::format($timespan->getOpens()))
                ->tillHour(OpeningHourFormatter::format($timespan->getCloses()));
        }

        return $day;
    }

    /**
     * @param OpeningHour[] $timespans
     * @return string[]
     */
    private function childcareLines(string $dayOfWeek, array $timespans, bool $withDayOfWeek): array
    {
        if (!$this->withChildcare) {
            return [];
        }

        // The timespans of a day share a single line, so a childcare that differs between
        // them is joined into one too instead of getting a line per timespan.
        $childcareOfDay = $this->openingHours->onDayOfWeek($dayOfWeek)->sharedChildcare();
        if ($childcareOfDay !== null) {
            return [$this->childcareLine([$childcareOfDay], $withDayOfWeek ? $dayOfWeek : '')];
        }

        $childcares = [];
        foreach ($timespans as $timespan) {
            $childcare = $timespan->getChildcare();
            if ($childcare !== null) {
                $childcares[] = $childcare;
            }
        }

        if ($childcares === []) {
            return [];
        }

        return [$this->childcareLine($childcares, $withDayOfWeek ? $dayOfWeek : '')];
    }

    /**
     * Childcare gets a line of its own, indented with a single space and between braces.
     */
    /**
     * @param Childcare[] $childcares
     */
    private function childcareLine(array $childcares, string $dayOfWeek): string
    {
        $childcareFormatter = ChildcareFormatter::forChildcares($childcares, $this->translator)->withBraces();

        if ($dayOfWeek !== '') {
            $childcareFormatter = $childcareFormatter->precededBy($this->translateDayOfWeek($dayOfWeek));
        }

        return ' ' . $childcareFormatter->toString();
    }

    /**
     * @return array<string, OpeningHour[]>
     */
    private function timespansPerDay(): array
    {
        $timespansPerDay = [];

        foreach ($this->openingHours as $openingHour) {
            foreach ($openingHour->getDaysOfWeek() as $dayOfWeek) {
                $timespansPerDay[$dayOfWeek][] = $openingHour;
            }
        }

        return $timespansPerDay;
    }

    private function translateDayOfWeek(string $dayOfWeek): string
    {
        return $this->formatter->formatDayOfWeekName($dayOfWeek);
    }
}
