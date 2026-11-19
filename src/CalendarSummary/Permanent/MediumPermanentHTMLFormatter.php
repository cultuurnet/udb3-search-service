<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Permanent;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\HtmlAvailabilityFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\OpeningHours;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use DateTimeImmutable;

final class MediumPermanentHTMLFormatter implements PermanentFormatterInterface
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
        if (!$offer->isAvailable()) {
            return HtmlAvailabilityFormatter::forOffer($offer, $this->translator)
                ->withElement('p')
                ->withoutBraces()
                ->capitalize()
                ->toString();
        }

        if (!$offer->getOpeningHours()->isEmpty()) {
            return $this->generateWeekScheme($offer->getOpeningHours());
        }

        return '<p class="cf-openinghours">' .
            ucfirst($this->translator->translate('open_every_day')) . '</p>';
    }

    /**
     * Generate a weekscheme based on the given opening hours.
     */
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
            return '<p class="cf-openinghours">' .
                ucfirst($this->translator->translate('open_every_day')) . '</p>';
        }

        if (count($weekDaysOpen) === 1) {
            return '<p class="cf-openinghours">' .
                $this->translator->translate('open_every') . ' ' .
                $this->formatter->formatAsDayOfWeek(new DateTimeImmutable(reset($weekDaysOpen))) . ' ' .
                $this->translator->translate('open_every_end') . '</p>';
        }

        $weekScheme = $this->getWeekScheme($weekDaysOpen, $this->formatter);

        $outputWeek = '<span>';
        $outputWeek .= $this->isFirstPeriodASingleDay($weekDaysOpen) ?
            ucfirst($this->translator->translate('open_at')) : ucfirst($this->translator->translate('open_at_consecutive_days'));
        $outputWeek .= ' <span class="cf-weekdays">';

        $i = 0;
        foreach ($weekScheme as $translatedDayNamesWithOpeningHour) {
            $outputWeek .= '<span class="cf-weekday-open">' . $translatedDayNamesWithOpeningHour . '</span>';
            if (++$i !== count($weekScheme)) {
                $outputWeek .= ' & ';
            }
        }

        $outputWeek .= '</span></span>';

        return $outputWeek;
    }
}
