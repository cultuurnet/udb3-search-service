<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class UniqueAddressTransformerTest extends TestCase
{
    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(array $inputData, bool $duplicatedPlacesPerUser, string $expectedResult): void
    {
        $transformer = new UniqueAddressTransformer($duplicatedPlacesPerUser);
        $result = $transformer->transform($inputData);

        $this->assertEquals($expectedResult, $result['unique_address_identifier']);
    }

    public function test_do_not_add_empty_unique_address_identifier(): void
    {
        $transformer = new UniqueAddressTransformer(false);
        $result = $transformer->transform([]);

        $this->assertArrayNotHasKey('unique_address_identifier', $result);
    }

    public function transformDataProvider(): array
    {
        return [
            'dutch main language per user' => [
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
                true,
                'dansstudio_teststraat_1_2000_antwerpen_be_john_doe',
            ],
            'dutch main language global' => [
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
                false,
                'dansstudio_teststraat_1_2000_antwerpen_be',
            ],
            'french main language per user' => [
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
                true,
                'dansstudio_teststraat_1_2000_antwerpen_be_john_doe',
            ],
            'french main language global' => [
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
                false,
                'dansstudio_teststraat_1_2000_antwerpen_be',
            ],
            'no main language per user' => [
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
                true,
                'dansstudio_teststraat_1_2000_antwerpen_be_john_doe',
            ],
            'no main language global' => [
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
                false,
                'dansstudio_teststraat_1_2000_antwerpen_be',
            ],
            'missing required fields per user' => [
                [
                    'name' => ['nl' => 'Dansstudio'],
                    'creator' => 'John Doe',
                ],
                true,
                'dansstudio_john_doe',
            ],
            'missing required fields global' => [
                [
                    'name' => ['nl' => 'Dansstudio'],
                    'creator' => 'John Doe',
                ],
                false,
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
                false,
                'teststraat_1_2000_antwerpen_be',
            ],
        ];
    }
}
