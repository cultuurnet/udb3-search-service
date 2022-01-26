<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class JsonDocumentTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_a_new_json_document_with_the_same_id_and_transformed_json(): void
    {
        $id = 'd1158d19-5c12-4242-85b8-d5ec62d71ca9';

        $originalData = ['foo' => 'bar', 'privateProperty' => 'secretValue'];
        $original = new JsonDocument($id, Json::encode($originalData));

        $expectedData = ['foo' => 'bar'];
        $expected = new JsonDocument($id, Json::encode($expectedData));

        $jsonTransformer = $this->createMock(JsonTransformer::class);

        $jsonTransformer->expects($this->once())
            ->method('transform')
            ->with($originalData)
            ->willReturn($expectedData);

        $jsonDocumentTransformer = new JsonDocumentTransformer($jsonTransformer);

        $actual = $jsonDocumentTransformer->transform($original);

        $this->assertEquals($expected, $actual);
    }
}
