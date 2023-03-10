<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\ElasticSearch\PredefinedQueryFieldsInterface;
use CultuurNet\UDB3\Search\Language\Language;

final class OrganizerPredefinedQueryStringFields implements PredefinedQueryFieldsInterface
{
    public function getPredefinedFields(Language ...$languages): array
    {
        $fields = [
            'id',
            'labels_free_text',
        ];

        foreach ($languages as $language) {
            $langCode = $language->getCode();
            $fields = array_merge(
                $fields,
                [
                    "name.{$langCode}",
                    "description.{$langCode}",
                    "address.{$langCode}.addressLocality",
                    "address.{$langCode}.postalCode",
                    "address.{$langCode}.streetAddress",
                ]
            );
        }

        return $fields;
    }
}
