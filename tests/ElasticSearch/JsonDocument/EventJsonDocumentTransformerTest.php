<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\JsonDocument\AssertsJsonDocuments;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\Region\RegionId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventJsonDocumentTransformerTest extends TestCase
{
    use AssertsJsonDocuments;

    /**
     * @var OfferRegionServiceInterface|MockObject
     */
    private $offerRegionService;

    /**
     * @var SimpleArrayLogger
     */
    private $simpleArrayLogger;

    /**
     * @var EventJsonDocumentTransformer
     */
    private $transformer;

    public function setUp()
    {
        $this->offerRegionService = $this->createMock(OfferRegionServiceInterface::class);

        $this->simpleArrayLogger = new SimpleArrayLogger();

        $this->transformer = new EventJsonDocumentTransformer(
            new PathEndIdUrlParser(),
            $this->offerRegionService,
            $this->simpleArrayLogger
        );
    }

    /**
     * @test
     */
    public function it_transforms_required_fields()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_events_with_duplicated_locations()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-duplicated-location.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-multiple-location-ids.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_logs_missing_required_fields()
    {
        $id = 'a9c2c833-5311-44bd-8cb8-b959196cb4b9';
        $originalDocument = new JsonDocument($id, '{}');

        // @codingStandardsIgnoreStart
        $expectedDocument = new JsonDocument(
            $id,
            '{"@type":"Event","isDuplicate":false,"originalEncodedJsonLd":"{}","audienceType":"everyone","mediaObjectsCount":0}'
        );
        // @codingStandardsIgnoreEnd

        $expectedLogs = [
            ['debug', "Transforming event $id for indexation.", []],
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
            ['debug', "Transformation of event $id finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertEquals($expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_transforms_events_with_multiple_dates()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-multiple-dates.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-multiple-dates.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_permanent_events_to_an_infinite_date_range()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-permanent.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-permanent.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_periodic_events()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-periodic.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-periodic.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_periodic_opening_hours_to_date_ranges()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-periodic-with-opening-hours.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-periodic-with-opening-hours.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_transforms_permanent_opening_hours_to_date_ranges()
    {
        Chronos::setTestNow(
            Chronos::createFromFormat(
                \DateTime::ATOM,
                '2017-05-09T15:11:32+02:00'
            )
        );

        $original = file_get_contents(__DIR__ . '/data/event/original-permanent-with-opening-hours.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-permanent-with-opening-hours.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_logs_incorrect_opening_hours_and_does_not_transform_them()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-periodic-with-wrong-opening-hours.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-periodic-without-date-range.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Missing expected field 'openingHours[0].dayOfWeek'.", []],
            ['warning', "Missing expected field 'openingHours[1].closes'.", []],
            ['warning', "Missing expected field 'openingHours[2].opens'.", []],
            ['warning', "Unknown day 'st. patrick's day' in opening hours.", []],
            ['warning', "Missing expected field 'subEvent'.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_does_not_polyfill_sub_event_for_unknown_calendar_types()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-wrong-calendar-type.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-wrong-calendar-type.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Could not polyfill subEvent for unknown calendarType 'foobar'.", []],
            ['warning', "Missing expected field 'subEvent'.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_logs_missing_start_date_when_sub_event_is_also_missing()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-without-start-date.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-without-start-date.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Missing expected field 'startDate'.", []],
            ['warning', "Missing expected field 'subEvent'.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_logs_missing_end_date_when_sub_event_is_also_missing()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-without-end-date.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-without-end-date.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Missing expected field 'endDate'.", []],
            ['warning', "Missing expected field 'subEvent'.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_logs_missing_start_and_end_date_in_sub_events()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-multiple-dates-and-wrong-sub-events.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-multiple-dates-without-date-range.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Missing expected field 'subEvent[0].startDate'.", []],
            ['warning', "Missing expected field 'subEvent[1].endDate'.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_transforms_optional_fields_if_present()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-optional-fields.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-optional-fields.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_typical_age_range_for_everyone_to_all_ages_true()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-for-all-ages.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-for-all-ages.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_skips_wrong_typical_age_range()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-wrong-typical-age-range.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-without-typical-age-range.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_skips_wrong_available_from()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-wrong-available-from.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-without-available-from.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ['error', "Could not parse availableFrom as an ISO-8601 datetime.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_uses_endDate_if_availableTo_is_malformed()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-wrong-available-to.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-end-date-as-available-to.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ['error', "Could not parse availableTo as an ISO-8601 datetime.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_uses_endDate_if_availableTo_is_missing()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-available-to-missing.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(
            __DIR__ . '/data/event/indexed-with-end-date-as-available-to-which-was-missing.json'
        );
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_makes_sure_availableFrom_is_never_higher_than_availableTo(): void
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-higher-available-from.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-same-available-from-and-to.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_adds_regions_if_there_are_any_matching()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-optional-fields.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-regions.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $this->offerRegionService->expects($this->once())
            ->method('getRegionIds')
            ->willReturn(
                [
                    new RegionId('prv-vlaams-brabant'),
                    new RegionId('gem-leuven'),
                ]
            );

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_modified_metadata_date()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-modified.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-modified.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_skips_wrong_created()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-wrong-created.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-without-created.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['error', "Could not parse created as an ISO-8601 datetime.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_skips_wrong_modified()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-wrong-modified.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-without-modified.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $expectedLogs = [
            ['debug', "Transforming event 23017cb7-e515-47b4-87c4-780735acc942 for indexation.", []],
            ['error', "Could not parse modified as an ISO-8601 datetime.", []],
            ['debug', "Transformation of event 23017cb7-e515-47b4-87c4-780735acc942 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->simpleArrayLogger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_should_copy_languages_and_completed_languages_if_present_on_the_json_ld()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-languages.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-copied-languages.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_should_copy_production_id_if_present_on_the_json_ld()
    {
        $original = file_get_contents(__DIR__ . '/data/event/original-with-production.json');
        $originalDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $original);

        $expected = file_get_contents(__DIR__ . '/data/event/indexed-with-production.json');
        $expectedDocument = new JsonDocument('23017cb7-e515-47b4-87c4-780735acc942', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }
}
