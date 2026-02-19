<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\PredefinedQueryFieldsInterface;
use CultuurNet\UDB3\Search\Language\Language;

final class OfferPredefinedQueryStringFields implements PredefinedQueryFieldsInterface
{
    private bool $useWordBreaker;

    public function __construct(bool $useWordBreaker = false)
    {
        $this->useWordBreaker = $useWordBreaker;
    }

    public function getPredefinedFields(Language ...$languages): array
    {
        $fields = [
            'id',
            $this->useWordBreaker ? 'labels_free_text.decompounder' : 'labels_free_text',
            'terms_free_text.id',
            $this->useWordBreaker ? 'terms_free_text.label.decompounder' : 'terms_free_text.label',
            $this->useWordBreaker ? 'performer_free_text.name.decompounder' : 'performer_free_text.name',
            'location.id',
            'organizer.id',
        ];

        foreach ($languages as $language) {
            $langCode = $language->getCode();
            $fields = array_merge(
                $fields,
                [
                    $this->useWordBreaker ? "name.{$langCode}.decompounder" : "name.{$langCode}",
                    $this->useWordBreaker ? "description.{$langCode}.decompounder" : "description.{$langCode}",
                    $this->useWordBreaker ? "address.{$langCode}.addressLocality.decompounder" : "address.{$langCode}.addressLocality",
                    "address.{$langCode}.postalCode",
                    $this->useWordBreaker ? "address.{$langCode}.streetAddress.decompounder" : "address.{$langCode}.streetAddress",
                    $this->useWordBreaker ? "location.name.{$langCode}.decompounder" : "location.name.{$langCode}",
                    $this->useWordBreaker ? "organizer.name.{$langCode}.decompounder" : "organizer.name.{$langCode}",
                ]
            );
        }

        return $fields;
    }
}
