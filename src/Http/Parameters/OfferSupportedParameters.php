<?php

namespace CultuurNet\UDB3\Search\Http\Parameters;

class OfferSupportedParameters extends AbstractSupportedParameters
{
    protected function getSupportedParameters(): array
    {
        return [
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
            'status',
            'termIds',
            'termLabels',
            'locationTermIds',
            'uitpas',
            'locationTermLabels',
            'organizerTermIds',
            'organizerTermLabels',
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
