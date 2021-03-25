<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

final class LoggerName
{
    /**
     * @var string
     */
    private $fileNameWithoutSuffix;

    /**
     * @var string
     */
    private $loggerName;

    public function __construct(string $fileNameWithoutSuffix, ?string $customLoggerName = null)
    {
        $this->fileNameWithoutSuffix = $fileNameWithoutSuffix;
        $this->loggerName = $customLoggerName ?? 'logger.' . $this->fileNameWithoutSuffix;
    }

    public static function forAmqpWorker(string $workerName, ?string $suffix = null): self
    {
        $fileName = 'amqp.' . $workerName;
        $loggerName = self::appendSuffixToFilename($fileName, $suffix);
        return new self($fileName, $loggerName);
    }

    public function getFileNameWithoutSuffix(): string
    {
        return $this->fileNameWithoutSuffix;
    }

    public function getLoggerName(): string
    {
        return $this->loggerName;
    }
}
