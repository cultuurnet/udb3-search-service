<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Offer\CalendarSummaryFormat;
use PHPUnit\Framework\TestCase;

final class CalendarSummaryEmbeddingJsonTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_embed_the_requested_calendar_summaries(): void
    {
        $jsonld = [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@context' => '/contexts/event',
            'status' => [
                'type' => 'Available',
            ],
            'bookingAvailability' => [
                'type' => 'Available',
            ],
            'calendarType' => 'permanent',
        ];

        $indexed = [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@context' => '/contexts/event',
            'originalEncodedJsonLd' => Json::encode($jsonld),
        ];

        $expected = [
            'calendarSummary' => [
                'nl' => [
                    'text' => [
                        'xs' => 'Alle dagen open',
                        'md' => 'Alle dagen open',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Alle dagen open</p>',
                    ],
                ],
                'fr' => [
                    'text' => [
                        'xs' => 'Ouvert tous les jours',
                        'md' => 'Ouvert tous les jours',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Ouvert tous les jours</p>',
                    ],
                ],
                'de' => [
                    'text' => [
                        'xs' => 'Jeden Tag geöffnet',
                        'md' => 'Jeden Tag geöffnet',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Jeden Tag geöffnet</p>',
                    ],
                ],
                'en' => [
                    'text' => [
                        'xs' => 'Open every day',
                        'md' => 'Open every day',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Open every day</p>',
                    ],
                ],
            ],
        ];

        $transformer = new CalendarSummaryEmbeddingJsonTransformer(
            CalendarSummaryFormat::fromCombinedParameter('xs-text'),
            CalendarSummaryFormat::fromCombinedParameter('md-text'),
            CalendarSummaryFormat::fromCombinedParameter('md-html')
        );

        $actual = $transformer->transform($indexed);
        $this->assertEquals($expected, $actual);
    }
}
