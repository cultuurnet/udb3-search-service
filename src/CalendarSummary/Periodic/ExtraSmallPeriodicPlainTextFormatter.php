<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary\Periodic;

use CultuurNet\UDB3\Search\CalendarSummary\DateComparison;
use CultuurNet\UDB3\Search\CalendarSummary\DateFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\PlainTextSummaryBuilder;
use CultuurNet\UDB3\Search\CalendarSummary\Translator;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;

final class ExtraSmallPeriodicPlainTextFormatter implements PeriodicFormatterInterface
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
        $startDate = $startDate->setTime(0, 0, 1);

        if (DateComparison::isInTheFuture($startDate)) {
            $plainTextSummaryBuilder = PlainTextSummaryBuilder::start($this->translator)
                ->fromPeriod(
                    $this->formatter->formatAsDayNumber($startDate),
                    $this->formatter->formatAsAbbreviatedMonthName($startDate)
                );
            if (!DateComparison::isCurrentYear($startDate)) {
                $plainTextSummaryBuilder = $plainTextSummaryBuilder->append($this->formatter->formatAsYear($startDate));
            }
            return $plainTextSummaryBuilder->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
                ->toString();
        }

        $endDate = $offer->getEndDate();
        $plainTextSummaryBuilder = PlainTextSummaryBuilder::start($this->translator)
            ->till(
                $this->formatter->formatAsDayNumber($endDate),
                $this->formatter->formatAsAbbreviatedMonthName($endDate)
            );
        if (!DateComparison::isCurrentYear($endDate)) {
            $plainTextSummaryBuilder = $plainTextSummaryBuilder->append($this->formatter->formatAsYear($endDate));
        }
        return $plainTextSummaryBuilder->appendAvailability($offer->getStatus(), $offer->getBookingAvailability())
            ->toString();
    }
}
