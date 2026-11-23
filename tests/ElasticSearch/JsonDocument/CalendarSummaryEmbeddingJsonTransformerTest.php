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

    /**
     * @test
     */
    public function it_leaves_the_draft_untouched_without_formats(): void
    {
        $transformer = new CalendarSummaryEmbeddingJsonTransformer();

        $this->assertSame(['foo' => 'bar'], $transformer->transform([], ['foo' => 'bar']));
    }

    /**
     * @test
     * @dataProvider providesOffers
     */
    public function it_summarizes_every_kind_of_offer(array $jsonld, string $format, array $expected): void
    {
        $transformer = new CalendarSummaryEmbeddingJsonTransformer(
            CalendarSummaryFormat::fromCombinedParameter($format)
        );
        [$size, $type] = explode('-', $format);

        $actual = $transformer->transform(['originalEncodedJsonLd' => Json::encode($jsonld)]);

        $this->assertSame(
            $expected,
            array_map(
                static fn (array $summaries): string => $summaries[$type][$size],
                $actual['calendarSummary']
            )
        );
    }

    public function providesOffers(): array
    {
        return [
            'single event' => [
                $this->event('single', '2099-03-10T20:00:00+01:00', '2099-03-10T22:00:00+01:00'),
                'md-text',
                [
                    'nl' => 'Di 10 maart 2099',
                    'fr' => 'Mar. 10 mars 2099',
                    'de' => 'Di. 10 März 2099',
                    'en' => 'Tue 10 March 2099',
                ],
            ],
            'multiple event' => [
                $this->event('multiple', '2099-03-10T20:00:00+01:00', '2099-03-12T22:00:00+01:00', [
                    $this->subEvent('2099-03-10T20:00:00+01:00', '2099-03-10T22:00:00+01:00'),
                    $this->subEvent('2099-03-12T20:00:00+01:00', '2099-03-12T22:00:00+01:00'),
                ]),
                'md-text',
                [
                    'nl' => "Di 10 maart 2099\nDo 12 maart 2099",
                    'fr' => "Mar. 10 mars 2099\nJeu. 12 mars 2099",
                    'de' => "Di. 10 März 2099\nDo. 12 März 2099",
                    'en' => "Tue 10 March 2099\nThu 12 March 2099",
                ],
            ],
            'periodic event' => [
                $this->event('periodic', '2099-03-10T00:00:00+01:00', '2099-04-10T23:59:59+02:00'),
                'md-text',
                [
                    'nl' => 'Van di 10 maart 2099 tot vr 10 april 2099',
                    'fr' => 'Du mar. 10 mars 2099 au ven. 10 avril 2099',
                    'de' => 'Von Di. 10 März 2099 bis Fr. 10 April 2099',
                    'en' => 'From Tue 10 March 2099 till Fri 10 April 2099',
                ],
            ],
            'cancelled single event' => [
                $this->event('single', '2099-03-10T20:00:00+01:00', '2099-03-10T22:00:00+01:00', null, 'Unavailable'),
                'md-text',
                [
                    'nl' => 'Di 10 maart 2099 (geannuleerd)',
                    'fr' => 'Mar. 10 mars 2099 (annulé)',
                    'de' => 'Di. 10 März 2099 (abgesagt)',
                    'en' => 'Tue 10 March 2099 (cancelled)',
                ],
            ],
            'sold out single event' => [
                $this->event('single', '2099-03-10T20:00:00+01:00', '2099-03-10T22:00:00+01:00', null, 'Available', 'Unavailable'),
                'md-text',
                [
                    'nl' => 'Di 10 maart 2099 (Volzet of uitverkocht)',
                    'fr' => 'Mar. 10 mars 2099 (Complet)',
                    'de' => 'Di. 10 März 2099 (Ausgebucht oder ausverkauft)',
                    'en' => 'Tue 10 March 2099 (Sold out or fully booked)',
                ],
            ],
            'permanently closed place' => [
                $this->place('Unavailable'),
                'md-html',
                [
                    'nl' => '<span class="cf-status">Permanent gesloten</span>',
                    'fr' => '<span class="cf-status">Fermé définitivement</span>',
                    'de' => '<span class="cf-status">Dauerhaft geschlossen</span>',
                    'en' => '<span class="cf-status">Permanently closed</span>',
                ],
            ],
            'temporarily closed place' => [
                $this->place('TemporarilyUnavailable'),
                'md-text',
                [
                    'nl' => 'Tijdelijk gesloten',
                    'fr' => 'Fermé temporairement',
                    'de' => 'Vorübergehend geschlossen',
                    'en' => 'Temporarily closed',
                ],
            ],
            'UTC dates shown in Brussels time' => [
                $this->event('single', '2099-03-10T23:30:00+00:00', '2099-03-11T00:30:00+00:00'),
                'lg-text',
                [
                    'nl' => 'Woensdag 11 maart 2099 van 00:30 tot 01:30',
                    'fr' => 'Mercredi 11 mars 2099 de 00:30 à 01:30',
                    'de' => 'Mittwoch 11 März 2099 von 00:30 bis 01:30',
                    'en' => 'Wednesday 11 March 2099 from 00:30 till 01:30',
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function it_embeds_a_format_that_is_requested_twice_once(): void
    {
        $transformer = new CalendarSummaryEmbeddingJsonTransformer(
            CalendarSummaryFormat::fromCombinedParameter('md-text'),
            CalendarSummaryFormat::fromCombinedParameter('md-text')
        );

        $actual = $transformer->transform([
            'originalEncodedJsonLd' => Json::encode($this->place('Available')),
        ]);

        $this->assertSame('Alle dagen open', $actual['calendarSummary']['nl']['text']['md']);
    }

    /**
     * @test
     */
    public function it_hides_past_dates_by_default(): void
    {
        $transformer = new CalendarSummaryEmbeddingJsonTransformer(
            CalendarSummaryFormat::fromCombinedParameter('md-text')
        );

        $actual = $transformer->transform(['originalEncodedJsonLd' => Json::encode($this->pastMultipleEvent())]);

        $this->assertSame('Evenement afgelopen', $actual['calendarSummary']['nl']['text']['md']);
    }

    /**
     * @test
     */
    public function it_can_include_past_dates(): void
    {
        $transformer = (new CalendarSummaryEmbeddingJsonTransformer(
            CalendarSummaryFormat::fromCombinedParameter('md-text')
        ))->withHidePast(false);

        $actual = $transformer->transform(['originalEncodedJsonLd' => Json::encode($this->pastMultipleEvent())]);

        $this->assertSame("Di 5 mei 2020\nDi 12 mei 2020", $actual['calendarSummary']['nl']['text']['md']);
    }

    private function pastMultipleEvent(): array
    {
        return $this->event('multiple', '2020-05-05T20:00:00+02:00', '2020-05-12T23:00:00+02:00', [
            $this->subEvent('2020-05-05T20:00:00+02:00', '2020-05-05T23:00:00+02:00'),
            $this->subEvent('2020-05-12T20:00:00+02:00', '2020-05-12T23:00:00+02:00'),
        ]);
    }

    private function event(
        string $calendarType,
        string $startDate,
        string $endDate,
        ?array $subEvents = null,
        string $status = 'Available',
        string $bookingAvailability = 'Available'
    ): array {
        return [
            '@context' => '/contexts/event',
            'status' => ['type' => $status],
            'bookingAvailability' => ['type' => $bookingAvailability],
            'calendarType' => $calendarType,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'subEvent' => $subEvents ?? [$this->subEvent($startDate, $endDate, $status, $bookingAvailability)],
        ];
    }

    private function subEvent(
        string $startDate,
        string $endDate,
        string $status = 'Available',
        string $bookingAvailability = 'Available'
    ): array {
        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => ['type' => $status],
            'bookingAvailability' => ['type' => $bookingAvailability],
        ];
    }

    private function place(string $status): array
    {
        return [
            '@context' => '/contexts/place',
            'status' => ['type' => $status],
            'bookingAvailability' => ['type' => 'Available'],
            'calendarType' => 'permanent',
        ];
    }
}
