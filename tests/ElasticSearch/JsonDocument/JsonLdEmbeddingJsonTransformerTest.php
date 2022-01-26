<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\Json;
use PHPUnit\Framework\TestCase;

final class JsonLdEmbeddingJsonTransformerTest extends TestCase
{
    /**
     * @var JsonLdEmbeddingJsonTransformer
     */
    private $transformer;

    protected function setUp(): void
    {
        $this->transformer = new JsonLdEmbeddingJsonTransformer();
    }

    /**
     * @test
     */
    public function it_should_return_a_document_with_only_the_embedded_json_ld(): void
    {
        $jsonLd = [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@type' => 'Event',
            'location' => [
                '@id' => 'https://io.uitdatabank.be/places/9361008e-4e5b-4060-ad49-0866c8fa1860',
                '@type' => 'Place',
                'address' => [
                    'nl' => [
                        'streetAddress' => 'Eenmeilaan 35',
                        'postalCode' => '3010',
                        'addressLocality' => 'Kessel-Lo',
                        'addressCountry' => 'BE',
                    ],
                ],
            ],
        ];

        $encodedJsonLd = Json::encode($jsonLd);

        $indexed = [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@type' => 'Event',
            'regions' => ['gem-leuven', 'prv-vlaams-brabant'],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Eenmeilaan 35',
                    'postalCode' => '3010',
                    'addressLocality' => 'Kessel-Lo',
                    'addressCountry' => 'BE',
                ],
            ],
            'location' => [
                '@id' => 'https://io.uitdatabank.be/places/9361008e-4e5b-4060-ad49-0866c8fa1860',
                '@type' => 'Place',
                'address' => [
                    'nl' => [
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

        $expected = $jsonLd;
        $actual = $this->transformer->transform($indexed);

        $this->assertEquals($expected, $actual);
    }
}
