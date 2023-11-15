<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class OriginalEncodedJsonLdTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new OriginalEncodedJsonLdTransformer();

        // Test data
        $inputData = [
            'key' => 'value',
            'contributors' => ['foo@test.be', 'bar@test.be'],
        ];

        // Expected result after transformation
        $expectedResult = [
            'originalEncodedJsonLd' => '{"key":"value"}',
        ];

        // Call the transform method
        $result = $transformer->transform($inputData);

        // Assert that the result matches the expected result
        $this->assertEquals($expectedResult, $result);
    }
}
