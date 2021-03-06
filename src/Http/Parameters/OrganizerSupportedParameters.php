<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

final class OrganizerSupportedParameters extends AbstractSupportedParameters
{
    protected function getSupportedParameters(): array
    {
        return [
            'q',
            'name',
            'website',
            'domain',
            'postalCode',
            'addressCountry',
            'creator',
            'labels',
            'textLanguages',
            'workflowStatus',
            'disableDefaultFilters',
            'sort',
        ];
    }
}
