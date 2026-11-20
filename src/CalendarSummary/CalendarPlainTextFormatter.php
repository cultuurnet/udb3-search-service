<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Middleware\NonAvailablePlacePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\ExtraSmallMultiplePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\SmallMultiplePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\CalendarType;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\ExtraLargePermanentPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\MediumPermanentPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\ExtraSmallSinglePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\LargeSinglePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\MediumSinglePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Single\SmallSinglePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Offer\Offer;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\ExtraLargePeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\ExtraSmallPeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\LargePeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\MediumPeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Periodic\SmallPeriodicPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Permanent\LargePermanentPlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\LargeMultiplePlainTextFormatter;
use CultuurNet\UDB3\Search\CalendarSummary\Multiple\MediumMultiplePlainTextFormatter;

final readonly class CalendarPlainTextFormatter implements CalendarFormatterInterface
{
    /**
     * @var array[]
     */
    private array $mapping;

    private NonAvailablePlacePlainTextFormatter $middleware;

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
                    'xl' => new LargeSinglePlainTextFormatter($translator),
                    'lg' => new LargeSinglePlainTextFormatter($translator),
                    'md' => new MediumSinglePlainTextFormatter($translator),
                    'sm' => new SmallSinglePlainTextFormatter($translator),
                    'xs' => new ExtraSmallSinglePlainTextFormatter($translator),
                ],
            CalendarType::multiple()->toString() =>
                [
                    'xl' => new LargeMultiplePlainTextFormatter($translator, $hidePastDates),
                    'lg' => new LargeMultiplePlainTextFormatter($translator, $hidePastDates),
                    'md' => new MediumMultiplePlainTextFormatter($translator, $hidePastDates),
                    'sm' => new SmallMultiplePlainTextFormatter($translator),
                    'xs' => new ExtraSmallMultiplePlainTextFormatter($translator),
                ],
            CalendarType::periodic()->toString() =>
                [
                    'xl' => new ExtraLargePeriodicPlainTextFormatter($translator),
                    'lg' => new LargePeriodicPlainTextFormatter($translator),
                    'md' => new MediumPeriodicPlainTextFormatter($translator),
                    'sm' => new SmallPeriodicPlainTextFormatter($translator),
                    'xs' => new ExtraSmallPeriodicPlainTextFormatter($translator),
                ],
            CalendarType::permanent()->toString() =>
                [
                    'xl' => new ExtraLargePermanentPlainTextFormatter($translator),
                    'lg' => new LargePermanentPlainTextFormatter($translator),
                    'md' => new MediumPermanentPlainTextFormatter($translator),
                    'sm' => new MediumPermanentPlainTextFormatter($translator),
                    'xs' => new MediumPermanentPlainTextFormatter($translator),
                ],
        ];

        $this->middleware = new NonAvailablePlacePlainTextFormatter($translator);
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
            fn ($offer) => $formatter->format($offer)
        );
    }
}
