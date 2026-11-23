<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\ClosedDay;

/**
 * Renders the periods during which there is no opening at all as a collapsible list.
 */
final readonly class HtmlClosedDaysFormatter
{
    private HtmlPeriodListFormatter $periodListFormatter;

    private DateFormatter $formatter;

    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->periodListFormatter = new HtmlPeriodListFormatter($translator);
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    /**
     * @param ClosedDay[] $closedDays
     * @param string[] $closedDaysOfWeek the days of the week that are closed every week,
     *   listed before the periods
     */
    public function format(array $closedDays, array $closedDaysOfWeek = []): string
    {
        $weeklyItems = array_map($this->generateClosedDayOfWeek(...), $closedDaysOfWeek);

        return $this->periodListFormatter->format($closedDays, 'cf-closed-days', 'closed', null, $weeklyItems);
    }

    private function generateClosedDayOfWeek(string $dayOfWeek): string
    {
        $label = $this->translator->translate('every') . ' '
            . $this->formatter->formatDayOfWeekName($dayOfWeek);

        return '<li>'
            . '<span class="cf-days">' . ucfirst($label) . '</span>'
            . '<span class="cf-closed cf-meta">' . $this->translator->translate('closed') . '</span>'
            . '</li>';
    }
}
