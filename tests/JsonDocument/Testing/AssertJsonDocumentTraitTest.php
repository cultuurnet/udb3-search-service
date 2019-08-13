<?php

namespace CultuurNet\UDB3\Search\JsonDocument\Testing;

use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\Search\Json\AssertJsonDocument;

class AssertJsonDocumentTraitTest extends TestCase
{
    use AssertJsonDocument;

    /**
     * @test
     */
    public function it_can_compare_properties_of_json_documents()
    {
        $ordered = json_encode(
            [
                'first' => 'gold',
                'second' =>  'silver',
                'third' => 'bronze'
            ]
        );
        $orderless = json_encode(
            [
                'second' =>  'silver',
                'first' => 'gold',
                'third' => 'bronze'
            ]
        );

        $originalDocument = new JsonDocument('1', $orderless);
        $expectedDocument = new JsonDocument('1', $ordered);

        $this->assertJsonDocumentPropertiesEquals($this, $expectedDocument, $originalDocument);
    }

    /**
     * @test
     */
    public function it_can_convert_compact_json_documents_to_pretty_print_json_documents()
    {
        $data = ['foo' => 'bar'];

        $compact = json_encode($data);
        $prettyPrint = json_encode($data, JSON_PRETTY_PRINT);

        $originalDocument = new JsonDocument('1', $compact);
        $expectedDocument = new JsonDocument('1', $prettyPrint);

        $actualDocument = $this->convertJsonDocumentFromCompactToPrettyPrint($originalDocument);

        $this->assertEquals($expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_can_convert_pretty_print_json_documents_to_compact_json_documents()
    {
        $data = ['foo' => 'bar'];

        $prettyPrint = json_encode($data, JSON_PRETTY_PRINT);
        $compact = json_encode($data);

        $originalDocument = new JsonDocument('1', $prettyPrint);
        $expectedDocument = new JsonDocument('1', $compact);

        $actualDocument = $this->convertJsonDocumentFromPrettyPrintToCompact($originalDocument);

        $this->assertEquals($expectedDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_can_assert_two_json_documents_are_equal_even_if_one_is_compact_and_the_other_is_pretty_print()
    {
        $data = ['foo' => 'bar'];

        $compact = json_encode($data);
        $prettyPrint = json_encode($data, JSON_PRETTY_PRINT);

        $compactDocument = new JsonDocument('1', $compact);
        $prettyPrintDocument = new JsonDocument('1', $prettyPrint);

        $this->assertJsonDocumentEquals($this, $compactDocument, $prettyPrintDocument);
    }
}
