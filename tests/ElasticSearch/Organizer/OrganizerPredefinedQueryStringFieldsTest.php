<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\Search\Language\Language;
use PHPUnit\Framework\TestCase;

final class OrganizerPredefinedQueryStringFieldsTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_predefined_query_string_fields(): void
    {
        $fields = (new OrganizerPredefinedQueryStringFields())->getPredefinedFields(
            new Language('nl'),
            new Language('de')
        );

        $this->assertEquals(
            [
                'id',
                'labels_free_text',
                'name.nl',
                'description.nl',
                'address.nl.addressLocality',
                'address.nl.postalCode',
                'address.nl.streetAddress',
                'name.de',
                'description.de',
                'address.de.addressLocality',
                'address.de.postalCode',
                'address.de.streetAddress',
            ],
            $fields
        );
    }
}
