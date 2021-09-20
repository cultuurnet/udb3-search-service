<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

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
            'originalEncodedJsonLd' => json_encode($jsonld),
        ];

        $expected = [
            'calendarSummary' => [
                'nl' => [
                    'text' => [
                        'xs' => 'Altijd open',
                        'md' => 'Altijd open',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Altijd open</p>',
                    ],
                ],
                'fr' => [
                    'text' => [
                        'xs' => 'Toujours ouvert',
                        'md' => 'Toujours ouvert',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Toujours ouvert</p>',
                    ],
                ],
                'de' => [
                    'text' => [
                        'xs' => 'Immer offen',
                        'md' => 'Immer offen',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Immer offen</p>',
                    ],
                ],
                'en' => [
                    'text' => [
                        'xs' => 'Always open',
                        'md' => 'Always open',
                    ],
                    'html' => [
                        'md' => '<p class="cf-openinghours">Always open</p>',
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
