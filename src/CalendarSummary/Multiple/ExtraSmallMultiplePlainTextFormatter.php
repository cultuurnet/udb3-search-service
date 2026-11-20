<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Multiple;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;

final class ExtraSmallMultiplePlainTextFormatter implements MultipleFormatterInterface
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
        $endDate = $offer->getEndDate();

        if (DateComparison::onSameDay($startDate, $endDate)) {
            $plainTextSummaryBuilder = PlainTextSummaryBuilder::start($this->translator)
                ->append($this->formatter->formatAsDayNumber($startDate))
                ->append($this->formatter->formatAsAbbreviatedMonthName($startDate));
            if (!DateComparison::isCurrentYear($startDate)) {
                $plainTextSummaryBuilder = $plainTextSummaryBuilder->append($this->formatter->formatAsYear($startDate));
            }

            return $plainTextSummaryBuilder->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
                ->toString();
        }

        $plainTextSummaryBuilder = PlainTextSummaryBuilder::start($this->translator)
            ->append($this->formatter->formatAsDayNumber($startDate))
            ->append($this->formatter->formatAsAbbreviatedMonthName($startDate));
        if (!DateComparison::isCurrentYear($startDate)) {
            $plainTextSummaryBuilder = $plainTextSummaryBuilder->append($this->formatter->formatAsYear($startDate));
        }
        $plainTextSummaryBuilder = $plainTextSummaryBuilder->append('-')
            ->append($this->formatter->formatAsDayNumber($endDate))
            ->append($this->formatter->formatAsAbbreviatedMonthName($endDate));
        if (!DateComparison::isCurrentYear($endDate)) {
            $plainTextSummaryBuilder = $plainTextSummaryBuilder->append($this->formatter->formatAsYear($endDate));
        }

        return $plainTextSummaryBuilder
            ->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
            ->toString();
    }
}
