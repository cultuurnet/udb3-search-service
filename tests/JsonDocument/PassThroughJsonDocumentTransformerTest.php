<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\JsonDocument\Testing\AssertJsonDocumentTrait;

class PassThroughJsonDocumentTransformerTest extends \PHPUnit_Framework_TestCase
{
    use AssertJsonDocumentTrait;

    /**
     * @var PassThroughJsonDocumentTransformer
     */
    private $transformer;

    public function setUp()
    {
        $this->transformer = new PassThroughJsonDocumentTransformer();
    }

    /**
     * @test
     */
    public function it_returns_exactly_the_same_json_document_as_it_was_given()
    {
        $originalDocument = new JsonDocument('c9b1a418-3e9c-450f-8d63-21e155e730ef', '{"foo":"bar"}');
        $expectedDocument = $originalDocument;
        $actualDocument = $this->transformer->transform($originalDocument);
        $this->assertJsonDocumentEquals($this, $expectedDocument, $actualDocument);
    }
}
