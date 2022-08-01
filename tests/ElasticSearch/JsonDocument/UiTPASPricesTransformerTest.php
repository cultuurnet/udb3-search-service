<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use PHPUnit\Framework\TestCase;

final class UiTPASPricesTransformerTest extends TestCase
{
    private UiTPASPricesTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new UiTPASPricesTransformer();
    }

    /**
     * @test
     */
    public function it_should_hide_uitpas_prices(): void
    {
        $draft = [
            'priceInfo' =>
                [
                    [
                        'category' => 'base',
                        'name' => [
                            'nl' => 'Basistarief',
                            'fr' => 'Tarif de base',
                            'en' => 'Base tariff',
                            'de' => 'Basisrate',
                        ],
                        'price' => 11,
                        'priceCurrency' => 'EUR',
                    ],
                    [
                        'category' => 'tariff',
                        'name' => [
                            'nl' => 'Senioren',
                            'fr' => 'Aînés',
                            'en' => 'Elderly',
                        ],
                        'price' => 6,
                        'priceCurrency' => 'EUR',
                    ],
                    [
                        'category' => 'uitpas',
                        'name' => [
                            'nl' => 'Kansentarief voor UiTPAS Regio Leuven',
                        ],
                        'price' => 1,
                        'priceCurrency' => 'EUR',
                    ],
                    [
                        'category' => 'uitpas',
                        'name' => [
                            'nl' => 'Kansentarief voor UiTPAS Regio Gent',
                        ],
                        'price' => 2,
                        'priceCurrency' => 'EUR',
                    ],
                ],
        ];

        $expected = [
            'priceInfo' =>
                [
                    [
                        'category' => 'base',
                        'name' => [
                            'nl' => 'Basistarief',
                            'fr' => 'Tarif de base',
                            'en' => 'Base tariff',
                            'de' => 'Basisrate',
                        ],
                        'price' => 11,
                        'priceCurrency' => 'EUR',
                    ],
                    [
                        'category' => 'tariff',
                        'name' => [
                            'nl' => 'Senioren',
                            'fr' => 'Aînés',
                            'en' => 'Elderly',
                        ],
                        'price' => 6,
                        'priceCurrency' => 'EUR',
                    ],
                ],
        ];

        $actual = $this->transformer->transform([], $draft);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_ignore_price_info_without_uitpas(): void
    {
        $draft = [
            'priceInfo' =>
                [
                    [
                        'category' => 'base',
                        'name' => [
                            'nl' => 'Basistarief',
                            'fr' => 'Tarif de base',
                            'en' => 'Base tariff',
                            'de' => 'Basisrate',
                        ],
                        'price' => 11,
                        'priceCurrency' => 'EUR',
                    ],
                    [
                        'category' => 'tariff',
                        'name' => [
                            'nl' => 'Senioren',
                            'fr' => 'Aînés',
                            'en' => 'Elderly',
                        ],
                        'price' => 6,
                        'priceCurrency' => 'EUR',
                    ],
                ],
        ];


        $actual = $this->transformer->transform([], $draft);
        $this->assertEquals($draft, $actual);
    }

    /**
     * @test
     */
    public function it_should_ignore_events_without_price_info(): void
    {
        $draft = [
            '@id' => 'https://io.uitdatabank.dev/event/fa3a2412-211c-4de1-b452-5a3ecea611f6',
            '@context' => '/contexts/event',
            'mainLanguage'=> 'nl',
            'name' => [
                'nl' => 'Onbekende prijs',
            ],
        ];

        $actual = $this->transformer->transform([], $draft);
        $this->assertEquals($draft, $actual);
    }
}
