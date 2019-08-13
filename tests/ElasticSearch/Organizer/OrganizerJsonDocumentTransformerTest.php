<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Organizer;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\Json\AssertJsonDocument;
use PHPUnit\Framework\TestCase;

class OrganizerJsonDocumentTransformerTest extends TestCase
{
    use AssertJsonDocument;

    /**
     * @var SimpleArrayLogger
     */
    private $logger;

    /**
     * @var OrganizerJsonDocumentTransformer
     */
    private $transformer;

    public function setUp()
    {
        $this->logger = new SimpleArrayLogger();

        $this->transformer = new OrganizerJsonDocumentTransformer(
            new PathEndIdUrlParser(),
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_keeps_the_properties_that_are_required_to_maintain_backwards_compatibility_with_the_api()
    {
        $original = file_get_contents(__DIR__ . '/data/original.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed.json');
        $expectedDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_handles_all_known_languages()
    {
        $original = file_get_contents(__DIR__ . '/data/all_languages_original.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expected = file_get_contents(__DIR__ . '/data/all_languages_indexed.json');
        $expectedDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_logs_missing_required_name_for_main_language()
    {
        $original = file_get_contents(__DIR__ . '/data/missing_main_language_original.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expected = file_get_contents(__DIR__ . '/data/missing_main_language_indexed.json');
        $expectedDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $expected);

        $expectedLogs = [
            ['warning', "Missing expected field 'name.nl'.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->logger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_handles_name_as_string()
    {
        $original = file_get_contents(__DIR__ . '/data/original_with_name_as_string.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed_with_name_as_string.json');
        $expectedDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_handles_translated_address()
    {
        $original = file_get_contents(__DIR__ . '/data/original_with_translated_address.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed_with_translated_address.json');
        $expectedDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_handles_non_translated_address()
    {
        $original = file_get_contents(__DIR__ . '/data/original_with_non_translated_address.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed_with_translated_address.json');
        $expectedDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_copies_workflow_status_if_provided()
    {
        $original = file_get_contents(__DIR__ . '/data/original_with_workflowstatus_deleted.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed_with_workflowstatus_deleted.json');
        $expectedDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_should_log_warnings_if_an_address_translation_is_incomplete()
    {
        $original = file_get_contents(__DIR__ . '/data/original_with_incomplete_translated_address.json');
        $originalDocument = new JsonDocument('5e0b3f9c-5947-46a0-b8f2-a1a5a37f3b83', $original);

        $expectedLogs = [
            ['warning', "Missing expected field 'address.nl.addressCountry'.", []],
            ['warning', "Missing expected field 'address.nl.addressLocality'.", []],
            ['warning', "Missing expected field 'address.nl.postalCode'.", []],
            ['warning', "Missing expected field 'address.nl.streetAddress'.", []],
        ];

        $this->transformer->transform($originalDocument);

        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }
}
