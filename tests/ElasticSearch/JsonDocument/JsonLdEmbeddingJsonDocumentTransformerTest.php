<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class JsonLdEmbeddingJsonDocumentTransformerTest extends TestCase
{
    /**
     * @var JsonLdEmbeddingJsonDocumentTransformer
     */
    private $transformer;

    public function setUp()
    {
        $this->transformer = new JsonLdEmbeddingJsonDocumentTransformer();
    }

    /**
     * @test
     */
    public function it_should_return_a_document_with_only_the_embedded_json_ld()
    {
        $id = '8ea290f6-deb2-426e-820a-68eeefde9c4d';

        $jsonLd = (object) [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@type' => 'Event',
            'location' => (object) [
                '@id' => 'https://io.uitdatabank.be/places/9361008e-4e5b-4060-ad49-0866c8fa1860',
                '@type' => 'Place',
                'address' => (object) [
                    'nl' => (object) [
                        'streetAddress' => 'Eenmeilaan 35',
                        'postalCode' => '3010',
                        'addressLocality' => 'Kessel-Lo',
                        'addressCountry' => 'BE',
                    ],
                ],
            ],
        ];

        $encodedJsonLd = json_encode($jsonLd);

        $indexed = (object) [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@type' => 'Event',
            'regions' => ['gem-leuven', 'prv-vlaams-brabant'],
            'address' => [
                'nl' => (object) [
                    'streetAddress' => 'Eenmeilaan 35',
                    'postalCode' => '3010',
                    'addressLocality' => 'Kessel-Lo',
                    'addressCountry' => 'BE',
                ],
            ],
            'location' => (object) [
                '@id' => 'https://io.uitdatabank.be/places/9361008e-4e5b-4060-ad49-0866c8fa1860',
                '@type' => 'Place',
                'address' => [
                    'nl' => (object) [
                        'streetAddress' => 'Eenmeilaan 35',
                        'postalCode' => '3010',
                        'addressLocality' => 'Kessel-Lo',
                        'addressCountry' => 'BE',
                    ],
                ],
            ],
            'foo' => 'bar',
            'originalEncodedJsonLd' => $encodedJsonLd,
        ];

        $expectedJsonLdDocument = new JsonDocument($id, $encodedJsonLd);
        $indexedDocument = new JsonDocument($id, json_encode($indexed));

        $actualJsonLdDocument = $this->transformer->transform($indexedDocument);

        $this->assertEquals($expectedJsonLdDocument, $actualJsonLdDocument);
        $this->assertEquals($jsonLd, $actualJsonLdDocument->getBody());
    }
}
