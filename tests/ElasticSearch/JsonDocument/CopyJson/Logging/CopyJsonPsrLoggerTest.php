<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CopyJsonPsrLoggerTest extends TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $psrLogger;

    /**
     * @var CopyJsonPsrLogger
     */
    private $copyJsonPsrLogger;

    protected function setUp()
    {
        $this->psrLogger = $this->createMock(LoggerInterface::class);

        $this->copyJsonPsrLogger = new CopyJsonPsrLogger(
            $this->psrLogger
        );
    }

    /**
     * @test
     */
    public function it_delegates_the_logging_of_a_missing_field()
    {
        $this->psrLogger->expects($this->once())
            ->method('warning')
            ->with("Missing expected field 'name.nl'.");

        $this->copyJsonPsrLogger->logMissingExpectedField('name.nl');
    }
}