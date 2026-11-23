<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Offer\ClosedDay;
use DateTimeImmutable;

/**
 * Renders the periods during which there is no opening at all as plain text.
 */
final readonly class PlainTextClosedDaysFormatter
{
    private PlainTextPeriodListFormatter $periodListFormatter;

    private DateFormatter $formatter;

    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->periodListFormatter = new PlainTextPeriodListFormatter($translator);
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
        $weeklyLines = array_map($this->generateClosedDayOfWeek(...), $closedDaysOfWeek);

        return $this->periodListFormatter->format($closedDays, 'closed', null, $weeklyLines);
    }

    private function generateClosedDayOfWeek(string $dayOfWeek): string
    {
        return PlainTextSummaryBuilder::start($this->translator)
            ->append($this->translator->translate('every'))
            ->append($this->formatter->formatAsDayOfWeek(new DateTimeImmutable($dayOfWeek)))
            ->closed()
            ->toString();
    }
}
