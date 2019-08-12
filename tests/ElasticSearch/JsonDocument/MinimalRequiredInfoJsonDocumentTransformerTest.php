<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\JsonDocument\Testing\AssertJsonDocumentTrait;

class MinimalRequiredInfoJsonDocumentTransformerTest extends \PHPUnit_Framework_TestCase
{
    use AssertJsonDocumentTrait;

    /**
     * @var MinimalRequiredInfoJsonDocumentTransformer
     */
    private $transformer;

    public function setUp()
    {
        $this->transformer = new MinimalRequiredInfoJsonDocumentTransformer();
    }

    /**
     * @test
     */
    public function it_returns_a_json_document_with_only_a_url_and_type()
    {
        $originalDocument = new JsonDocument(
            'b1a44077-722b-4a72-9621-50cb8dbb46db',
            json_encode(
                [
                    '@id' => 'http://foo.io/events/b1a44077-722b-4a72-9621-50cb8dbb46db',
                    '@type' => 'Event',
                    'name' => 'Punkfest',
                    'foo' => 'bar',
                    'lorem' => 'ipsum',
                ]
            )
        );

        $expectedDocument = new JsonDocument(
            'b1a44077-722b-4a72-9621-50cb8dbb46db',
            json_encode(
                [
                    '@id' => 'http://foo.io/events/b1a44077-722b-4a72-9621-50cb8dbb46db',
                    '@type' => 'Event',
                ]
            )
        );

        $actualDocument = $this->transformer->transform($originalDocument);

        $this->assertJsonDocumentEquals($this, $expectedDocument, $actualDocument);
    }
}
