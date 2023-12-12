<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class AddUniqueHashToPlaceTransformerTest extends TestCase
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

    public function test_do_not_add_empty_hash(): void
    {
        $transformer = new AddUniqueHashToPlaceTransformer();
        $result = $transformer->transform([]);

        $this->assertArrayNotHasKey('hash', $result);
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
                'dansstudio_teststraat 1_2000_antwerpen_be_john doe',
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
                'dansstudio_teststraat 1_2000_antwerpen_be_john doe',
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
                'dansstudio_teststraat 1_2000_antwerpen_be_john doe',
            ],
            'missing required fields' => [
                [
                    'name' => ['nl' => 'Dansstudio'],
                    'creator' => 'John Doe',
                ],
                'dansstudio_john doe',
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
                'teststraat 1_2000_antwerpen_be',
            ],
        ];
    }
}
