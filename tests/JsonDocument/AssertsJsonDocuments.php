<?php

namespace CultuurNet\UDB3\Search\JsonDocument;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

trait AssertsJsonDocuments
{
    /**
     * @param TestCase $testCase
     * @param JsonDocument $expected
     * @param JsonDocument $actual
     */
    private function assertJsonDocumentPropertiesEquals(
        TestCase $testCase,
        JsonDocument $expected,
        JsonDocument $actual
    ) {
        $expected = json_decode($expected->getRawBody());
        $actual = json_decode($actual->getRawBody());
        $testCase->assertEquals($expected, $actual);
    }

    /**
     * @param TestCase $testCase
     * @param JsonDocument $expected
     * @param JsonDocument $actual
     */
    private function assertJsonDocumentEquals(
        TestCase $testCase,
        JsonDocument $expected,
        JsonDocument $actual
    ) {
        $expected = $this->convertJsonDocumentFromCompactToPrettyPrint($expected);
        $actual = $this->convertJsonDocumentFromCompactToPrettyPrint($actual);
        $testCase->assertEquals($expected, $actual);
    }

    /**
     * @param JsonDocument $jsonDocument
     * @return JsonDocument
     */
    private function convertJsonDocumentFromCompactToPrettyPrint(JsonDocument $jsonDocument)
    {
        $body = $jsonDocument->getBody();
        $jsonWithoutPrettyPrint = json_encode($body, JSON_PRETTY_PRINT);
        return new JsonDocument($jsonDocument->getId(), $jsonWithoutPrettyPrint);
    }

    /**
     * @param JsonDocument $jsonDocument
     * @return JsonDocument
     */
    private function convertJsonDocumentFromPrettyPrintToCompact(JsonDocument $jsonDocument)
    {
        $body = $jsonDocument->getBody();
        $jsonWithoutPrettyPrint = json_encode($body);
        return new JsonDocument($jsonDocument->getId(), $jsonWithoutPrettyPrint);
    }
}
