<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeImmutable;

final readonly class MediumPermanentPlainTextFormatter implements PermanentFormatterInterface
{
    use MediumPermanentWeekScheme;

    private DateFormatter $formatter;

    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    public function format(Offer $offer): string
    {
        if ($offer->getStatus()->getType() === 'Unavailable') {
            return ucfirst($this->translator->translate('cancelled'));
        }

        if ($offer->getStatus()->getType() === 'TemporarilyUnavailable') {
            return ucfirst($this->translator->translate('postponed'));
        }

        if (!$offer->getOpeningHours()->isEmpty()) {
            return $this->generateWeekScheme($offer->getOpeningHours());
        }

        return PlainTextSummaryBuilder::start($this->translator)
            ->alwaysOpen()
            ->startNewLine()
            ->toString();
    }

    private function generateWeekScheme(OpeningHours $openingHours): string
    {
        $weekDaysOpen = [];
        // Create a list of all day names that have opening hours
        foreach ($openingHours as $openingHour) {
            foreach ($openingHour->getDaysOfWeek() as $dayName) {
                if (!in_array($dayName, $weekDaysOpen, true)) {
                    $weekDaysOpen[(int) $this->formatter->formatAsDayOfWeekNumber(new DateTimeImmutable($dayName))] = $dayName;
                }
            }
        }

        if (count($weekDaysOpen) === 7) {
            return PlainTextSummaryBuilder::start($this->translator)
                ->alwaysOpen()
                ->startNewLine()
                ->toString();
        }

        if (count($weekDaysOpen) === 1) {
            return PlainTextSummaryBuilder::start($this->translator)
                ->append($this->translator->translate('open_every'))
                ->append($this->formatter->formatAsDayOfWeek(new DateTimeImmutable(reset($weekDaysOpen))))
                ->append($this->translator->translate('open_every_end'))
                ->toString();
        }

        $weekScheme = $this->getWeekScheme($weekDaysOpen, $this->formatter);

        // Put all the day names with opening hours on a single line with 'Open at' (sic) at the beginning.
        // E.g. 'Open at mo & th - su'
        if ($this->isFirstPeriodASingleDay($weekDaysOpen)) {
            return PlainTextSummaryBuilder::start($this->translator)
                ->openAt(...$weekScheme)
                ->toString();
        }

        // Put all the day names with opening hours on a single line with 'Open at' (sic) at the beginning.
        // E.g. 'Open at mo - th & su'
        return PlainTextSummaryBuilder::start($this->translator)
            ->openFrom(...$weekScheme)
            ->toString();
    }
}
