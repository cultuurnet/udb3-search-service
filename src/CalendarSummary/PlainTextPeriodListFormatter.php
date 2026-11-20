<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Period;

/**
 * Renders a list of periods as a heading followed by a line per period.
 */
final readonly class PlainTextPeriodListFormatter
{
    private DateFormatter $formatter;

    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    /**
     * @param Period[] $periods
     * @param callable(Period): string|null $formatContent
     *   Renders the lines between the dates and the description of a period.
     * @param string[] $leadingLines lines that precede the periods, right below the heading
     */
    public function format(
        array $periods,
        string $headingTranslationKey,
        ?callable $formatContent = null,
        array $leadingLines = []
    ): string {
        $upcomingPeriods = $this->withoutPastPeriods($periods);

        if (!$upcomingPeriods && !$leadingLines) {
            return '';
        }

        $lines = array_merge([ucfirst($this->translator->translate($headingTranslationKey))], $leadingLines);

        foreach ($upcomingPeriods as $period) {
            $lines[] = $this->generatePeriod($period, $formatContent);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * @template T of Period
     * @param T[] $periods
     * @return T[]
     */
    private function withoutPastPeriods(array $periods): array
    {
        return array_filter($periods, static fn (Period $period): bool => !DateComparison::isPastDay($period->getEndDate()));
    }

    /**
     * @param callable(Period): string|null $formatContent
     */
    private function generatePeriod(Period $period, ?callable $formatContent): string
    {
        $startDate = $period->getStartDate();
        $endDate = $period->getEndDate();

        $dates = PlainTextSummaryBuilder::start($this->translator)
            ->append($this->formatter->formatAsDayOfWeek($startDate))
            ->append($this->formatter->formatAsFullDate($startDate));

        if (!DateComparison::onSameDay($startDate, $endDate)) {
            $dates = $dates->tillIncluded(
                $this->formatter->formatAsDayOfWeek($endDate),
                $this->formatter->formatAsFullDate($endDate)
            );
        }

        // The description names the period, e.g. 'Herfstvakantie', so it belongs with the dates
        // instead of after the opening hours that follow them.
        $description = $period->getDescriptionForLanguage($this->translator->getLanguageCode());
        if ($description !== '') {
            $dates = $dates->append('(' . $description . ')');
        }

        $lines = [$dates->toString()];

        $content = $formatContent !== null ? $formatContent($period) : '';
        if ($content !== '') {
            $lines[] = $content;
        }

        return implode(PHP_EOL, $lines);
    }
}
