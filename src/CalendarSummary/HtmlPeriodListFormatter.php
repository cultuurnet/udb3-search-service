<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\Period;

/**
 * Renders a list of periods as a collapsible list.
 */
final readonly class HtmlPeriodListFormatter
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
     *   Renders the markup that follows the dates and the description of a period.
     * @param string[] $leadingItems rendered list items that precede the periods
     */
    public function format(
        array $periods,
        string $cssClass,
        string $summaryTranslationKey,
        ?callable $formatContent = null,
        array $leadingItems = []
    ): string {
        $upcomingPeriods = DateComparison::withoutPastPeriods($periods);

        if (!$upcomingPeriods && !$leadingItems) {
            return '';
        }

        $output = $this->openDetails($cssClass, $summaryTranslationKey) . implode('', $leadingItems);

        foreach ($upcomingPeriods as $period) {
            $output .= $this->generatePeriod($period, $formatContent);
        }

        return $output . '</ul></details>';
    }

    private function openDetails(string $class, string $summaryTranslationKey): string
    {
        return '<details class="' . $class . '">'
            . '<summary>' . ucfirst($this->translator->translate($summaryTranslationKey)) . '</summary>'
            . '<ul class="list-unstyled">';
    }

    /**
     * @param callable(Period): string|null $formatContent
     */
    private function generatePeriod(Period $period, ?callable $formatContent): string
    {
        $startDate = $period->getStartDate();
        $endDate = $period->getEndDate();

        $output = '<li>'
            . '<span class="cf-date">'
            . ucfirst($this->formatter->formatAsDayOfWeek($startDate))
            . ' ' . $this->formatter->formatAsFullDate($startDate)
            . '</span>';

        if (!DateComparison::onSameDay($startDate, $endDate)) {
            $output .= '<span class="cf-to cf-meta">' . $this->translator->translate('till_included') . '</span>'
                . '<span class="cf-date">'
                . $this->formatter->formatAsDayOfWeek($endDate)
                . ' ' . $this->formatter->formatAsFullDate($endDate)
                . '</span>';
        }

        $description = $period->getDescriptionForLanguage($this->translator->getLanguageCode());
        if ($description !== '') {
            $output .= '<span class="cf-description">(' . $description . ')</span>';
        }

        if ($formatContent !== null) {
            $output .= $formatContent($period);
        }

        return $output . '</li>';
    }
}
