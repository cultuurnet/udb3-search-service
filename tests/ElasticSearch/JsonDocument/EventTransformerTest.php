<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\Region\RegionId;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventTransformerTest extends TestCase
{
    /**
     * @var OfferRegionServiceInterface|MockObject
     */
    private $offerRegionService;

    /**
     * @var SimpleArrayLogger
     */
    private $simpleArrayLogger;

    /**
     * @var EventTransformer
     */
    private $transformer;

    protected function setUp(): void
    {
        $this->offerRegionService = $this->createMock(OfferRegionServiceInterface::class);

        $this->simpleArrayLogger = new SimpleArrayLogger();

        $this->transformer = new EventTransformer(
            new JsonTransformerPsrLogger(
                $this->simpleArrayLogger
            ),
            new PathEndIdUrlParser(),
            $this->offerRegionService
        );
    }

    /**
     * @test
     */
    public function it_transforms_required_fields(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original.json',
            __DIR__ . '/data/event/indexed.json'
        );
    }

    /**
     * @test
     */
    public function it_transforms_events_with_duplicated_locations(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-duplicated-location.json',
            __DIR__ . '/data/event/indexed-with-multiple-location-ids.json'
        );
    }

    /**
     * @test
     */
    public function it_logs_missing_required_fields(): void
    {
        $original = [];

        $expected = [
            '@type' => 'Event',
            'isDuplicate' => false,
            'originalEncodedJsonLd' => '{}',
            'audienceType' => 'everyone',
            'mediaObjectsCount' => 0,
        ];

        $expectedLogs = [
            ['warning', "Missing expected field '@id'.", []],
            ['warning', "Missing expected field 'mainLanguage'.", []],
            ['warning', "Missing expected field 'languages'.", []],
            ['warning', "Missing expected field 'completedLanguages'.", []],
            ['warning', "Missing expected field 'name'.", []],
            ['warning', "Missing expected field 'calendarType'.", []],
            ['warning', "Missing expected field 'creator'.", []],
            ['warning', "Missing expected field 'created'.", []],
            ['warning', "Missing expected field 'workflowStatus'.", []],
            ['warning', "Missing expected field 'location'.", []],
        ];

        $actual = $this->transformer->transform($original, []);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedLogs, $this->simpleArrayLogger->getLogs());
    }

    /**
     * @test
     */
    public function it_transforms_events_with_multiple_dates(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-multiple-dates.json',
            __DIR__ . '/data/event/indexed-with-multiple-dates.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_permanent_events_to_an_infinite_date_range(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-permanent.json',
            __DIR__ . '/data/event/indexed-permanent.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_periodic_events(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-periodic.json',
            __DIR__ . '/data/event/indexed-periodic.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_periodic_opening_hours_to_date_ranges(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-periodic-with-opening-hours.json',
            __DIR__ . '/data/event/indexed-periodic-with-opening-hours.json'
        );
    }

    /**
     * @test
     */
    public function it_transforms_permanent_opening_hours_to_date_ranges(): void
    {
        Chronos::setTestNow(
            Chronos::createFromFormat(
                DateTime::ATOM,
                '2017-05-09T15:11:32+02:00'
            )
        );

        $this->transformAndAssert(
            __DIR__ . '/data/event/original-permanent-with-opening-hours.json',
            __DIR__ . '/data/event/indexed-permanent-with-opening-hours.json'
        );
    }

    /**
     * @test
     */
    public function it_logs_incorrect_opening_hours_and_does_not_transform_them(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-periodic-with-wrong-opening-hours.json',
            __DIR__ . '/data/event/indexed-periodic-without-date-range.json',
            [
                ['warning', "Missing expected field 'openingHours[0].dayOfWeek'.", []],
                ['warning', "Missing expected field 'openingHours[1].closes'.", []],
                ['warning', "Missing expected field 'openingHours[2].opens'.", []],
                ['warning', "Unknown day 'st. patrick's day' in opening hours.", []],
                ['warning', "Missing expected field 'subEvent'.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_does_not_polyfill_sub_event_for_unknown_calendar_types(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-wrong-calendar-type.json',
            __DIR__ . '/data/event/indexed-with-wrong-calendar-type.json',
            [
                ['warning', "Could not polyfill subEvent for unknown calendarType 'foobar'.", []],
                ['warning', "Missing expected field 'subEvent'.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_missing_start_date_when_sub_event_is_also_missing(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-without-start-date.json',
            __DIR__ . '/data/event/indexed-without-start-date.json',
            [
                ['warning', "Missing expected field 'startDate'.", []],
                ['warning', "Missing expected field 'subEvent'.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_missing_end_date_when_sub_event_is_also_missing(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-without-end-date.json',
            __DIR__ . '/data/event/indexed-without-end-date.json',
            [
                ['warning', "Missing expected field 'endDate'.", []],
                ['warning', "Missing expected field 'subEvent'.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_logs_missing_start_and_end_date_in_sub_events(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-multiple-dates-and-wrong-sub-events.json',
            __DIR__ . '/data/event/indexed-with-multiple-dates-without-date-range.json',
            [
                ['warning', "Missing expected field 'subEvent[0].startDate'.", []],
                ['warning', "Missing expected field 'subEvent[1].endDate'.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_optional_fields_if_present(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-optional-fields.json',
            __DIR__ . '/data/event/indexed-with-optional-fields.json',
            [
                ['warning', 'Missing expected field \'calendarType\'.', []],
                ['warning', 'Found availableFrom but workflowStatus is DRAFT.', []],
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_typical_age_range_for_everyone_to_all_ages_true(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-for-all-ages.json',
            __DIR__ . '/data/event/indexed-for-all-ages.json'
        );
    }

    /**
     * @test
     */
    public function it_skips_wrong_typical_age_range(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-wrong-typical-age-range.json',
            __DIR__ . '/data/event/indexed-without-typical-age-range.json'
        );
    }

    /**
     * @test
     */
    public function it_skips_wrong_available_from(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-wrong-available-from.json',
            __DIR__ . '/data/event/indexed-without-available-from.json',
            [
                ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
                ['error', "Could not parse availableFrom as an ISO-8601 datetime.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_uses_endDate_if_availableTo_is_malformed(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-wrong-available-to.json',
            __DIR__ . '/data/event/indexed-with-end-date-as-available-to.json',
            [
                ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
                ['error', "Could not parse availableTo as an ISO-8601 datetime.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_uses_endDate_if_availableTo_is_missing(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-available-to-missing.json',
            __DIR__ . '/data/event/indexed-with-end-date-as-available-to-which-was-missing.json',
            [
                ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_makes_sure_availableFrom_is_never_higher_than_availableTo(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-higher-available-from.json',
            __DIR__ . '/data/event/indexed-with-same-available-from-and-to.json',
            [
                ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_adds_regions_if_there_are_any_matching(): void
    {
        $this->offerRegionService->expects($this->once())
            ->method('getRegionIds')
            ->willReturn(
                [
                    new RegionId('prv-vlaams-brabant'),
                    new RegionId('gem-leuven'),
                ]
            );

        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-optional-fields.json',
            __DIR__ . '/data/event/indexed-with-regions.json',
            [
                ['warning', 'Missing expected field \'calendarType\'.', []],
                ['warning', 'Found availableFrom but workflowStatus is DRAFT.', []],
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_modified_metadata_date(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-modified.json',
            __DIR__ . '/data/event/indexed-modified.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_skips_wrong_created(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-wrong-created.json',
            __DIR__ . '/data/event/indexed-without-created.json',
            [
                ['error', "Could not parse created as an ISO-8601 datetime.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_skips_wrong_modified(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-wrong-modified.json',
            __DIR__ . '/data/event/indexed-without-modified.json',
            [
                ['error', "Could not parse modified as an ISO-8601 datetime.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_should_copy_languages_and_completed_languages_if_present_on_the_json_ld(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-languages.json',
            __DIR__ . '/data/event/indexed-with-copied-languages.json'
        );
    }

    /**
     * @test
     */
    public function it_should_copy_production_id_if_present_on_the_json_ld(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/event/original-with-production.json',
            __DIR__ . '/data/event/indexed-with-production.json'
        );
    }

    private function transformAndAssert(string $givenFilePath, string $expectedFilePath, array $expectedLogs = []): void
    {
        $original = json_decode(file_get_contents($givenFilePath), true);

        // Compare the expected and actual JSON as objects, not arrays. Some Elasticsearch fields expect an empty object
        // specifically instead of an empty array in some scenario's. But if we decode to arrays, empty JSON objects
        // become empty arrays in PHP.
        $expected = json_decode(file_get_contents($expectedFilePath));
        $actual = json_decode(
            json_encode(
                $this->transformer->transform($original, [])
            )
        );

        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedLogs, $this->simpleArrayLogger->getLogs());
    }
}
