<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

final class MediumPeriodicPlainTextFormatter implements PeriodicFormatterInterface
{
    /**
     * @var DateFormatter
     */
    private $formatter;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->formatter = new DateFormatter($translator->getLocale());
        $this->translator = $translator;
    }

    public function format(Offer $offer): string
    {
        $startDate = $offer->getStartDate();
        $formattedStartDate = $this->formatter->formatAsFullDate($startDate);
        $formattedStartDayOfWeek = $this->formatter->formatAsAbbreviatedDayOfWeek($startDate);

        $endDate = $offer->getEndDate();
        $formattedEndDate = $this->formatter->formatAsFullDate($endDate);
        $formattedEndDayOfWeek = $this->formatter->formatAsAbbreviatedDayOfWeek($endDate);

        $summaryBuilder = PlainTextSummaryBuilder::start($this->translator);

        if ($formattedStartDate === $formattedEndDate) {
            return $summaryBuilder->append($formattedStartDayOfWeek)
                ->append($formattedStartDate)
                ->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
                ->toString();
        }

        return $summaryBuilder
            ->from($formattedStartDayOfWeek, $formattedStartDate)
            ->till($formattedEndDayOfWeek, $formattedEndDate)
            ->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
            ->toString();
    }
}
