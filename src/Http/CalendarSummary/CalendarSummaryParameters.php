<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\CalendarSummary;

use CultuurNet\UDB3\Search\CalendarSummary\Locales;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeZone;

final readonly class CalendarSummaryParameters
{
    private const DEFAULT_TIMEZONE = 'Europe/Brussels';

    public string $locale;

    public CalendarSummaryFormat $format;

    public bool $hidePast;

    public string $timezone;

    public function __construct(ApiRequestInterface $request)
    {
        $language = self::singleValue($request, 'language', 'nl');
        if (!isset(Locales::BY_LANGUAGE[$language])) {
            throw new UnsupportedParameterValue(
                'Invalid language: ' . $language . '. Use one of: ' . implode(',', array_keys(Locales::BY_LANGUAGE))
            );
        }
        $this->locale = Locales::BY_LANGUAGE[$language];

        $this->format = new CalendarSummaryFormat(
            self::typeFromAcceptHeader($request),
            self::singleValue($request, 'size', 'lg')
        );

        $this->hidePast = filter_var(self::singleValue($request, 'hidePast', 'false'), FILTER_VALIDATE_BOOLEAN);

        // Entry API never validated the timezone, so rejecting an unknown one would break existing integrations.
        // Including the backwards compatible names keeps legacy identifiers like US/Eastern working.
        $timezone = self::singleValue($request, 'timezone', self::DEFAULT_TIMEZONE);
        $this->timezone = in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)
            ? $timezone
            : self::DEFAULT_TIMEZONE;
    }

    /**
     * Mirrors Entry API: only the media type before the first ";" counts, so q-weighted content negotiation is
     * ignored and anything other than exactly text/html falls back to plain text instead of failing.
     */
    private static function typeFromAcceptHeader(ApiRequestInterface $request): string
    {
        $mediaType = trim(explode(';', $request->getHeaderLine('Accept'))[0]);
        return $mediaType === 'text/html' ? 'html' : 'text';
    }

    private static function singleValue(ApiRequestInterface $request, string $name, string $default): string
    {
        $value = $request->getQueryParam($name, $default);
        if (!is_string($value)) {
            throw new UnsupportedParameterValue('The "' . $name . '" parameter must be a single text value.');
        }
        return $value;
    }
}
