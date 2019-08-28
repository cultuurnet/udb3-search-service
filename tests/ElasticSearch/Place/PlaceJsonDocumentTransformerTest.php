<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\Place;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ElasticSearch\Offer\OfferRegionServiceInterface;
use CultuurNet\UDB3\Search\ElasticSearch\PathEndIdUrlParser;
use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\JsonDocument\AssertsJsonDocuments;
use CultuurNet\UDB3\Search\Region\RegionId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceJsonDocumentTransformerTest extends TestCase
{
    use AssertsJsonDocuments;

    /**
     * @var OfferRegionServiceInterface|MockObject
     */
    private $offerRegionService;

    /**
     * @var SimpleArrayLogger
     */
    private $logger;

    /**
     * @var PlaceJsonDocumentTransformer
     */
    private $transformer;

    public function setUp()
    {
        $this->offerRegionService = $this->createMock(OfferRegionServiceInterface::class);

        $this->logger = new SimpleArrayLogger();

        $this->transformer = new PlaceJsonDocumentTransformer(
            new PathEndIdUrlParser(),
            $this->offerRegionService,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_transforms_required_fields()
    {
        $original = file_get_contents(__DIR__ . '/data/original.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

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
            '{"@type":"Place","originalEncodedJsonLd":"{}","mainLanguage":"nl","audienceType":"everyone","mediaObjectsCount":0}'
        );
        // @codingStandardsIgnoreEnd

        $expectedLogs = [
            ['debug', "Transforming place $id for indexation.", []],
            ['warning', "Missing expected field '@id'.", []],
            ['warning', "Missing expected field 'name'.", []],
            ['warning', "Missing expected field 'creator'.", []],
            ['warning', "Missing expected field 'workflowStatus'.", []],
            ['warning', "Missing expected field 'address'.", []],
            ['warning', "Missing expected field 'calendarType'.", []],
            ['warning', "Missing expected field 'mainLanguage'.", []],
            ['warning', "Missing expected field 'created'.", []],
            ['debug', "Transformation of place $id finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->logger->getLogs();

        $this->assertEquals($expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_should_log_a_warning_if_address_is_not_found_in_the_main_language()
    {
        $original = file_get_contents(__DIR__ . '/data/original-without-address-in-main-language.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expectedLogs = [
            ['debug', "Transforming place 179c89c5-dba4-417b-ae96-62e7a12c2405 for indexation.", []],
            ['warning', "Missing expected field 'address.nl'.", []],
            ['debug', "Transformation of place 179c89c5-dba4-417b-ae96-62e7a12c2405 finished.", []],
        ];

        $this->transformer->transform($originalDocument);

        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }

    /**
     * @test
     */
    public function it_should_log_warnings_if_an_address_translation_is_incomplete()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-incomplete-address-translation.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expectedLogs = [
            ['debug', "Transforming place 179c89c5-dba4-417b-ae96-62e7a12c2405 for indexation.", []],
            ['warning', "Missing expected field 'address.fr.addressCountry'.", []],
            ['warning', "Missing expected field 'address.fr.addressLocality'.", []],
            ['warning', "Missing expected field 'address.fr.postalCode'.", []],
            ['warning', "Missing expected field 'address.fr.streetAddress'.", []],
            ['debug', "Transformation of place 179c89c5-dba4-417b-ae96-62e7a12c2405 finished.", []],
        ];

        $this->transformer->transform($originalDocument);

        $this->assertEquals($expectedLogs, $this->logger->getLogs());
    }

    /**
     * @test
     */
    public function it_transforms_optional_fields_if_present()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-optional-fields.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-with-optional-fields.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_typical_age_range_for_everyone_to_all_ages_true()
    {
        $original = file_get_contents(__DIR__ . '/data/original-for-all-ages.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-for-all-ages.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_a_periodic_place_to_a_date_range()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-period.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-with-period.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_a_periodic_place_with_opening_hours_to_a_date_range()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-period-and-opening-hours.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-with-period-and-opening-hours.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_transforms_a_permanent_place_with_opening_hours_to_a_date_range()
    {
        Chronos::setTestNow(
            Chronos::createFromFormat(
                \DateTime::ATOM,
                '2017-05-09T15:11:32+02:00'
            )
        );

        $original = file_get_contents(__DIR__ . '/data/original-with-opening-hours.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-with-opening-hours.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_adds_regions_if_there_are_any_matching()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-optional-fields.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-with-regions.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

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
    public function it_skips_wrong_available_from()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-wrong-available-from.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-without-available-from.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $expectedLogs = [
            ['debug', "Transforming place 179c89c5-dba4-417b-ae96-62e7a12c2405 for indexation.", []],
            ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ['error', "Could not parse availableFrom as an ISO-8601 datetime.", []],
            ['debug', "Transformation of place 179c89c5-dba4-417b-ae96-62e7a12c2405 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->logger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_uses_endDate_if_availableTo_is_malformed()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-wrong-available-to.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-with-end-date-as-available-to.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $expectedLogs = [
            ['debug', "Transforming place 179c89c5-dba4-417b-ae96-62e7a12c2405 for indexation.", []],
            ['warning', "Found availableFrom but workflowStatus is DRAFT.", []],
            ['error', "Could not parse availableTo as an ISO-8601 datetime.", []],
            ['debug', "Transformation of place 179c89c5-dba4-417b-ae96-62e7a12c2405 finished.", []],
        ];

        $actualDocument = $this->transformer->transform($originalDocument);
        $actualLogs = $this->logger->getLogs();

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
        $this->assertEquals($expectedLogs, $actualLogs);
    }

    /**
     * @test
     */
    public function it_transforms_modified_metadata_date()
    {
        $original = file_get_contents(__DIR__ . '/data/original-modified.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-modified.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }

    /**
     * @test
     * @group tmp
     */
    public function it_should_transform_addresses_in_a_deprecated_format()
    {
        $original = file_get_contents(__DIR__ . '/data/original-with-deprecated-address-format.json');
        $originalDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $original);

        $expected = file_get_contents(__DIR__ . '/data/indexed-from-deprecated-address.json');
        $expectedDocument = new JsonDocument('179c89c5-dba4-417b-ae96-62e7a12c2405', $expected);

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $actualDocument);
    }
}
