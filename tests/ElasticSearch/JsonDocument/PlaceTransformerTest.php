<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\FileReader;
use DateTimeInterface;
use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\Region\RegionServiceInterface;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\Region\RegionId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class PlaceTransformerTest extends TestCase
{
    /**
     * @var RegionServiceInterface&MockObject
     */
    private $regionService;

    private SimpleArrayLogger $logger;

    private PlaceTransformer $transformer;

    protected function setUp(): void
    {
        Chronos::setTestNow(Chronos::createFromFormat(DateTimeInterface::ATOM, '2017-05-09T15:11:32+02:00'));

        $this->regionService = $this->createMock(RegionServiceInterface::class);

        $this->logger = new SimpleArrayLogger();

        $this->transformer = new PlaceTransformer(
            new JsonTransformerPsrLogger(
                $this->logger
            ),
            new PathEndIdUrlParser(),
            $this->regionService
        );
    }

    /**
     * @test
     */
    public function it_transforms_required_fields(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original.json',
            __DIR__ . '/data/place/indexed.json'
        );
    }

    /**
     * @test
     */
    public function it_logs_missing_required_fields(): void
    {
        $original = [];

        $expected = [
            '@type' => 'Place',
            'isDuplicate' => false,
            'originalEncodedJsonLd' => '{}',
            'audienceType' => 'everyone',
            'mediaObjectsCount' => 0,
            'videosCount' => 0,
            'metadata' => [
                'popularity' => 0,
            ],
            'status' => 'Available',
            'bookingAvailability' => 'Available',
            'indexedAt' => '2017-05-09T15:11:32+02:00',
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
            ['warning', "Missing expected field 'address'.", []],
        ];

        $actual = $this->transformer->transform($original, []);
        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }

    /**
     * @test
     */
    public function it_should_log_a_warning_if_address_is_not_found_in_the_main_language(): void
    {
        $original = FileReader::read(__DIR__ . '/data/place/original-without-address-in-main-language.json');

        $expectedLogs = [
            ['warning', "Missing expected field 'address.nl'.", []],
        ];

        $this->transformer->transform(Json::decodeAssociatively($original), []);

        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }

    /**
     * @test
     */
    public function it_should_log_warnings_if_an_address_translation_is_incomplete(): void
    {
        $original = FileReader::read(__DIR__ . '/data/place/original-with-incomplete-address-translation.json');

        $expectedLogs = [
            ['warning', "Missing expected field 'address.fr.addressCountry'.", []],
            ['warning', "Missing expected field 'address.fr.addressLocality'.", []],
            ['warning', "Missing expected field 'address.fr.postalCode'.", []],
            ['warning', "Missing expected field 'address.fr.streetAddress'.", []],
        ];

        $this->transformer->transform(Json::decodeAssociatively($original), []);

        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }

    /**
     * @test
     */
    public function it_transforms_optional_fields_if_present(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-optional-fields.json',
            __DIR__ . '/data/place/indexed-with-optional-fields.json',
            [
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
            __DIR__ . '/data/place/original-for-all-ages.json',
            __DIR__ . '/data/place/indexed-for-all-ages.json'
        );
    }

    /**
     * @test
     */
    public function it_transforms_a_periodic_place_to_a_date_range(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-period.json',
            __DIR__ . '/data/place/indexed-with-period.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_a_periodic_place_with_opening_hours_to_a_date_range(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-period-and-opening-hours.json',
            __DIR__ . '/data/place/indexed-with-period-and-opening-hours.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_a_permanent_place_with_opening_hours_to_a_date_range(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-opening-hours.json',
            __DIR__ . '/data/place/indexed-with-opening-hours.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_adds_regions_if_there_are_any_matching(): void
    {
        $this->regionService->expects($this->once())
            ->method('getRegionIds')
            ->willReturn(
                [
                    new RegionId('prv-vlaams-brabant'),
                    new RegionId('gem-leuven'),
                ]
            );

        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-optional-fields.json',
            __DIR__ . '/data/place/indexed-with-regions.json',
            [
                ['warning', 'Found availableFrom but workflowStatus is DRAFT.', []],
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_skips_wrong_available_from(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-wrong-available-from.json',
            __DIR__ . '/data/place/indexed-without-available-from.json',
            [
                ['warning', 'Found availableFrom but workflowStatus is DRAFT.', []],
                ['error', 'Could not parse availableFrom as an ISO-8601 datetime.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_uses_endDate_if_availableTo_is_malformed(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-wrong-available-to.json',
            __DIR__ . '/data/place/indexed-with-end-date-as-available-to.json',
            [
                ['warning', 'Found availableFrom but workflowStatus is DRAFT.', []],
                ['error', 'Could not parse availableTo as an ISO-8601 datetime.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_modified_metadata_date(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-modified.json',
            __DIR__ . '/data/place/indexed-modified.json',
            [
                ['warning', 'Missing expected field \'creator\'.', []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_transforms_duplicateOf_to_isDuplicate(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-duplicate-of.json',
            __DIR__ . '/data/place/indexed-duplicate.json'
        );
    }

    /**
     * @test
     */
    public function it_transforms_metadata(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-metadata.json',
            __DIR__ . '/data/place/indexed-with-metadata.json'
        );
    }

    /**
     * @test
     */
    public function it_transforms_contributors(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-contributors.json',
            __DIR__ . '/data/place/indexed-with-contributors.json'
        );
    }

    /**
     * @test
     */
    public function it_transforms_completeness(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/place/original-with-contributors.json',
            __DIR__ . '/data/place/indexed-with-contributors.json'
        );
    }

    private function transformAndAssert(string $givenFilePath, string $expectedFilePath, array $expectedLogs = []): void
    {
        $original = Json::decodeAssociatively(FileReader::read($givenFilePath));

        // Compare the expected and actual JSON as objects, not arrays. Some Elasticsearch fields expect an empty object
        // specifically instead of an empty array in some scenario's. But if we decode to arrays, empty JSON objects
        // become empty arrays in PHP.
        $expected = Json::decode(FileReader::read($expectedFilePath));
        $actual = Json::decode(
            Json::encode(
                $this->transformer->transform($original, [])
            )
        );

        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }
}
