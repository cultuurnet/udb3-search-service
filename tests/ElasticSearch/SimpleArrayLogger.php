<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Psr\Log\LoggerInterface;

// This array logger makes it easy to also check the order of method calls.
// Could be moved to separate library for reuse on other projects.
final class SimpleArrayLogger implements LoggerInterface
{
    private array $logs = [];

    public function emergency($message, array $context = []): void
    {
        $this->logs[] = ['emergency', $message, $context];
    }

    public function alert($message, array $context = []): void
    {
        $this->logs[] = ['alert', $message, $context];
    }

    public function critical($message, array $context = []): void
    {
        $this->logs[] = ['critical', $message, $context];
    }

    public function error($message, array $context = []): void
    {
        $this->logs[] = ['error', $message, $context];
    }

    public function warning($message, array $context = []): void
    {
        $this->logs[] = ['warning', $message, $context];
    }

    public function notice($message, array $context = []): void
    {
        $this->logs[] = ['notice', $message, $context];
    }

    public function info($message, array $context = []): void
    {
        $this->logs[] = ['info', $message, $context];
    }

    public function debug($message, array $context = []): void
    {
        $this->logs[] = ['debug', $message, $context];
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [$level, $message, $context];
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
