<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\Region\RegionServiceInterface;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use CultuurNet\UDB3\Search\Region\RegionId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrganizerTransformerTest extends TestCase
{
    /**
     * @var RegionServiceInterface|MockObject
     */
    private $regionService;

    private SimpleArrayLogger $logger;

    private OrganizerTransformer $transformer;

    protected function setUp(): void
    {
        $this->regionService = $this->createMock(RegionServiceInterface::class);

        $this->logger = new SimpleArrayLogger();

        $this->transformer = new OrganizerTransformer(
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
    public function it_copies_the_required_properties(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/organizer/original.json',
            __DIR__ . '/data/organizer/indexed.json'
        );
    }

    /**
     * @test
     */
    public function it_handles_all_known_languages(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/organizer/all_languages_original.json',
            __DIR__ . '/data/organizer/all_languages_indexed.json'
        );
    }

    /**
     * @test
     */
    public function it_logs_missing_required_name_for_main_language(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/organizer/missing_main_language_original.json',
            __DIR__ . '/data/organizer/missing_main_language_indexed.json',
            [
                ['warning', "Missing expected field 'name.nl'.", []],
            ]
        );
    }

    /**
     * @test
     */
    public function it_handles_translated_address(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/organizer/original_with_translated_address.json',
            __DIR__ . '/data/organizer/indexed_with_translated_address.json'
        );
    }

    /**
     * @test
     */
    public function it_converts_images_to_image_count(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/organizer/original_with_images.json',
            __DIR__ . '/data/organizer/indexed_with_images.json'
        );
    }

    /**
     * @test
     */
    public function it_copies_workflow_status_if_provided(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/organizer/original_with_workflowstatus_deleted.json',
            __DIR__ . '/data/organizer/indexed_with_workflowstatus_deleted.json'
        );
    }

    /**
     * @test
     */
    public function it_transforms_contributors(): void
    {
        $this->transformAndAssert(
            __DIR__ . '/data/organizer/original-with-contributors.json',
            __DIR__ . '/data/organizer/indexed-with-contributors.json'
        );
    }

    /**
     * @test
     */
    public function it_should_log_warnings_if_an_address_translation_is_incomplete(): void
    {
        $original = Json::decodeAssociatively(
            file_get_contents(__DIR__ . '/data/organizer/original_with_incomplete_translated_address.json')
        );

        $expectedLogs = [
            ['warning', "Missing expected field 'address.nl.addressCountry'.", []],
            ['warning', "Missing expected field 'address.nl.addressLocality'.", []],
            ['warning', "Missing expected field 'address.nl.postalCode'.", []],
            ['warning', "Missing expected field 'address.nl.streetAddress'.", []],
        ];

        $this->transformer->transform($original);

        $this->assertEquals($expectedLogs, $this->logger->getLogs());
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
            __DIR__ . '/data/organizer/original_with_geo.json',
            __DIR__ . '/data/organizer/indexed_with_regions.json'
        );
    }

    private function transformAndAssert(string $givenFilePath, string $expectedFilePath, array $expectedLogs = []): void
    {
        $original = Json::decodeAssociatively(file_get_contents($givenFilePath));

        // Compare the expected and actual JSON as objects, not arrays. Some Elasticsearch fields expect an empty object
        // specifically instead of an empty array in some scenario's. But if we decode to arrays, empty JSON objects
        // become empty arrays in PHP.
        $expected = Json::decode(file_get_contents($expectedFilePath));
        $actual = Json::decode(
            Json::encode(
                $this->transformer->transform($original, [])
            )
        );

        $this->assertEquals($expected, $actual);
        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }
}
