<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

final class OfferSupportedParameters extends AbstractSupportedParameters
{
    protected function getSupportedParameters(): array
    {
        return [
            'embedCalendarSummaries',
            'q',
            'id',
            'text',
            'locationId',
            'organizerId',
            'availableFrom',
            'availableTo',
            'workflowStatus',
            'regions',
            'coordinates',
            'distance',
            'bounds',
            'postalCode',
            'addressCountry',
            'minAge',
            'maxAge',
            'allAges',
            'price',
            'minPrice',
            'maxPrice',
            'audienceType',
            'hasMediaObjects',
            'hasVideos',
            'labels',
            'locationLabels',
            'organizerLabels',
            'textLanguages',
            'mainLanguage',
            'languages',
            'completedLanguages',
            'calendarType',
            'dateFrom',
            'dateTo',
            'localTimeFrom',
            'localTimeTo',
            'status',
            'attendanceMode',
            'bookingAvailability',
            'termIds',
            'termLabels',
            'locationTermIds',
            'uitpas',
            'locationTermLabels',
            'organizerTermIds',
            'organizerTermLabels',
            'recommendationFor',
            'facets',
            'creator',
            'sort',
            'createdFrom',
            'createdTo',
            'modifiedFrom',
            'modifiedTo',
            'disableDefaultFilters',
            'isDuplicate',
            'productionId',
            'groupBy',
        ];
    }
}
