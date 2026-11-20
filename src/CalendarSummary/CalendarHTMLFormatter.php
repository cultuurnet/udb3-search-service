<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Middleware\FormatterMiddleware;
use CultuurNet\UDB3\Search\CalendarSummary\Middleware\NonAvailablePlaceHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\ExtraSmallMultipleHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\MediumMultipleHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\LargeMultipleHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\SmallMultipleHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\ExtraLargePeriodicHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\ExtraLargePermanentHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\MediumPermanentHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\LargeSingleHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\MediumSingleHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\SmallSingleHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\LargePermanentHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\ExtraSmallPeriodicHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\LargePeriodicHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\MediumPeriodicHTMLFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\SmallPeriodicHTMLFormatter;

final class CalendarHTMLFormatter implements CalendarFormatterInterface
{
    /**
     * @var array[]
     */
    private $mapping;

    /**
     * @var FormatterMiddleware
     */
    private $middleware;

    public function __construct(
        string $langCode = 'nl_BE',
        bool $hidePastDates = false,
        string $timeZone = 'Europe/Brussels'
    ) {
        date_default_timezone_set($timeZone);

        $translator = new Translator($langCode);

        $this->mapping = [
            CalendarType::single()->toString() =>
                [
                    'xl' => new LargeSingleHTMLFormatter($translator),
                    'lg' => new LargeSingleHTMLFormatter($translator),
                    'md' => new MediumSingleHTMLFormatter($translator),
                    'sm' => new SmallSingleHTMLFormatter($translator),
                    'xs' => new SmallSingleHTMLFormatter($translator),
                ],
            CalendarType::multiple()->toString() =>
                [
                    'xl' => new LargeMultipleHTMLFormatter($translator, $hidePastDates),
                    'lg' => new LargeMultipleHTMLFormatter($translator, $hidePastDates),
                    'md' => new MediumMultipleHTMLFormatter($translator, $hidePastDates),
                    'sm' => new SmallMultipleHTMLFormatter($translator),
                    'xs' => new ExtraSmallMultipleHTMLFormatter($translator),
                ],
            CalendarType::periodic()->toString() =>
                [
                    'xl' => new ExtraLargePeriodicHTMLFormatter($translator),
                    'lg' => new LargePeriodicHTMLFormatter($translator),
                    'md' => new MediumPeriodicHTMLFormatter($translator),
                    'sm' => new SmallPeriodicHTMLFormatter($translator),
                    'xs' => new ExtraSmallPeriodicHTMLFormatter($translator),
                ],
            CalendarType::permanent()->toString() =>
                [
                    'xl' => new ExtraLargePermanentHTMLFormatter($translator),
                    'lg' => new LargePermanentHTMLFormatter($translator),
                    'md' => new MediumPermanentHTMLFormatter($translator),
                    'sm' => new MediumPermanentHTMLFormatter($translator),
                    'xs' => new MediumPermanentHTMLFormatter($translator),
                ],
        ];

        $this->middleware = new NonAvailablePlaceHTMLFormatter($translator);
    }

    /**
     * @throws FormatterException
     */
    public function format(Offer $offer, string $format): string
    {
        $calenderType = $offer->getCalendarType()->toString();

        if (isset($this->mapping[$calenderType][$format])) {
            $formatter = $this->mapping[$calenderType][$format];
        } else {
            throw new FormatterException($format . ' format not supported for ' . $calenderType);
        }

        return $this->middleware->format(
            $offer,
            function ($offer) use ($formatter) {
                return $formatter->format($offer);
            }
        );
    }
}
