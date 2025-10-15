<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class UniqueAddressTransformerTest extends TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(array $inputData, string $expectedResult, string $expectedResultV2): void
    {
        $transformer = new UniqueAddressTransformer();
        $result = $transformer->transform($inputData);

        $this->assertEquals($expectedResult, $result['unique_address_identifier']);
        $this->assertEquals($expectedResultV2, $result['unique_address_identifier_v2']);
    }

    public function test_do_not_add_empty_unique_address_identifier(): void
    {
        $transformer = new UniqueAddressTransformer();
        $result = $transformer->transform([]);

        $this->assertArrayNotHasKey('unique_address_identifier', $result);
        $this->assertArrayNotHasKey('unique_address_identifier_v2', $result);
    }

    public function transformDataProvider(): array
    {
        return [
            'dutch main language' =>[
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
                'dansstudio_teststraat_1_2000_antwerpen_be_john_doe',
                'dansstudio_teststraat_1_2000_antwerpen_be',
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
                'dansstudio_teststraat_1_2000_antwerpen_be_john_doe',
                'dansstudio_teststraat_1_2000_antwerpen_be',
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
                'dansstudio_teststraat_1_2000_antwerpen_be_john_doe',
                'dansstudio_teststraat_1_2000_antwerpen_be',
            ],
            'missing required fields' => [
                [
                    'name' => ['nl' => 'Dansstudio'],
                    'creator' => 'John Doe',
                ],
                'dansstudio_john_doe',
                'dansstudio',
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
                'teststraat_1_2000_antwerpen_be',
                'teststraat_1_2000_antwerpen_be',
            ],
        ];
    }
}
