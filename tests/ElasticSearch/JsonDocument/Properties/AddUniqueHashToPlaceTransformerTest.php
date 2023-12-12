<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

class AddUniqueHashToPlaceTransformerTest extends TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(array $inputData, string $expectedResult): void
    {
        $transformer = new AddUniqueHashToPlaceTransformer();
        $result = $transformer->transform($inputData);

        $this->assertEquals($expectedResult, $result['hash']);
    }

    public function transformDataProvider(): array
    {
        return [
            [
                [
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
                ],
                sha1('DansstudioTeststraat 12000AntwerpenBEJohn Doe')
            ],
            'french main language' => [
                [
                    'name' => ['fr' => 'Dansstudio'],
                    'mainLanguage' => 'fr',
                    'address' => [
                        'fr' => [
                            'streetAddress' => 'Teststraat 1',
                            'postalCode' => '2000',
                            'addressLocality' => 'Antwerpen',
                            'addressCountry' => 'BE',
                        ],
                    ],
                    'creator' => 'John Doe',
                ],
                sha1('DansstudioTeststraat 12000AntwerpenBEJohn Doe')
            ],
            'no man language' => [
                [
                    'name' => ['nl' => 'Dansstudio'],
                    'address' => [
                        'nl' => [
                            'streetAddress' => 'Teststraat 1',
                            'postalCode' => '2000',
                            'addressLocality' => 'Antwerpen',
                            'addressCountry' => 'BE',
                        ],
                    ],
                    'creator' => 'John Doe',
                ],
                sha1('DansstudioTeststraat 12000AntwerpenBEJohn Doe')
            ],
            'missing required fields' => [
                [
                    'name' => ['nl' => 'Dansstudio'],
                    'creator' => 'John Doe',
                ],
                sha1('DansstudioJohn Doe')
            ],
            'missing required name and creator' => [
                [
                    'address' => [
                        'nl' => [
                            'streetAddress' => 'Teststraat 1',
                            'postalCode' => '2000',
                            'addressLocality' => 'Antwerpen',
                            'addressCountry' => 'BE',
                        ],
                    ],
                ],
                sha1('Teststraat 12000AntwerpenBE')
            ],
        ];
    }
}
