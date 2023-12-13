<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class UniqueAddressTransformerTest extends TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(array $inputData, string $expectedResult): void
    {
        $transformer = new UniqueAddressTransformer();
        $result = $transformer->transform($inputData);

        $this->assertEquals($expectedResult, $result['unique_address_identifier']);
    }

    public function test_do_not_add_empty_unique_address_identifier(): void
    {
        $transformer = new UniqueAddressTransformer();
        $result = $transformer->transform([]);

        $this->assertArrayNotHasKey('unique_address_identifier', $result);
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
            'no main language' => [
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
