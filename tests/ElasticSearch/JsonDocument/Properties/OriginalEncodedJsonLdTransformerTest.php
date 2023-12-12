<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\Json;
use PHPUnit\Framework\TestCase;

final class OriginalEncodedJsonLdTransformerTest extends TestCase
{
    public function testTransform()
    {
        $inputData = [
            'name' => ['nl' => 'Dansstudio'],
            'mainLanguage' => 'nl',
            'address' => [
                'nl' => [
                    'streetAddress' => 'Teststraat 1',
                    'postalCode' => '2000',
                    'addressLocality' => 'Antwerpen',
                    'addressCountry' => 'BE',
                ],
            ],
            'creator' => 'John Doe',
        ];

        $result = (new OriginalEncodedJsonLdTransformer())
            ->transform($inputData);

        $this->assertArrayNotHasKey('hash', $result);
        $this->assertEquals(Json::encodeWithOptions((object)$inputData, JSON_UNESCAPED_SLASHES), $result['originalEncodedJsonLd']);
    }
}
