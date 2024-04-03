<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class JsonTransformerPsrLoggerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $psrLogger;


    private JsonTransformerPsrLogger $jsonTransformerPsrLogger;

    protected function setUp(): void
    {
        $this->psrLogger = $this->createMock(LoggerInterface::class);

        $this->jsonTransformerPsrLogger = new JsonTransformerPsrLogger(
            $this->psrLogger
        );
    }

    /**
     * @test
     */
    public function it_delegates_the_logging_of_a_missing_field(): void
    {
        $this->psrLogger->expects($this->once())
            ->method('warning')
            ->with("Missing expected field 'name.nl'.");

        $this->jsonTransformerPsrLogger->logMissingExpectedField('name.nl');
    }
}
