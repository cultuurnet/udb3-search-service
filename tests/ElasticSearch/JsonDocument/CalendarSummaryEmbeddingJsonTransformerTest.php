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
        $original = [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@type' => 'Event',
            'status' => [
                'type' => 'Available',
            ],
            'calendarType' => 'permanent',
        ];

        $expected = [
            '@id' => 'https://io.uitdatabank.be/events/8ea290f6-deb2-426e-820a-68eeefde9c4d',
            '@type' => 'Event',
            'status' => [
                'type' => 'Available',
            ],
            'calendarType' => 'permanent',
            'calendarSummary' => [
                'text' => [
                    'xs' => '',
                ],
                'html' => [
                    'md' => '',
                ],
            ],
        ];

        $transformer = new CalendarSummaryEmbeddingJsonTransformer(
            CalendarSummaryFormat::fromCombinedParameter('xs-text'),
            CalendarSummaryFormat::fromCombinedParameter('md-html')
        );

        $actual = $transformer->transform($original);
        $this->assertEquals($expected, $actual);
    }
}
