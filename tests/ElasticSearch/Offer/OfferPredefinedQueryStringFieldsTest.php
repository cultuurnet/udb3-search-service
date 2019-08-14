<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Language;
use PHPUnit\Framework\TestCase;

class OfferPredefinedQueryStringFieldsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_predefined_query_string_fields()
    {
        $fields = (new OfferPredefinedQueryStringFields())->getPredefinedFields(
            new Language('nl'),
            new Language('fr')
        );

        $this->assertEquals(
            [
                'id',
                'labels_free_text',
                'terms_free_text.id',
                'terms_free_text.label',
                'performer_free_text.name',
                'location.id',
                'organizer.id',
                "name.nl",
                "description.nl",
                "address.nl.addressLocality",
                "address.nl.postalCode",
                "address.nl.streetAddress",
                "location.name.nl",
                "organizer.name.nl",
                "name.fr",
                "description.fr",
                "address.fr.addressLocality",
                "address.fr.postalCode",
                "address.fr.streetAddress",
                "location.name.fr",
                "organizer.name.fr",
            ],
            $fields
        );
    }
}
