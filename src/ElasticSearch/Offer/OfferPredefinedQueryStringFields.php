<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\PredefinedQueryFieldsInterface;
use CultuurNet\UDB3\Search\Language\Language;

class OfferPredefinedQueryStringFields implements PredefinedQueryFieldsInterface
{
    public function getPredefinedFields(Language ...$languages): array
    {
        $fields = [
            'id',
            'labels_free_text',
            'terms_free_text.id',
            'terms_free_text.label',
            'performer_free_text.name',
            'location.id',
            'organizer.id',
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
                    "location.name.{$langCode}",
                    "organizer.name.{$langCode}",
                ]
            );
        }

        return $fields;
    }
}
