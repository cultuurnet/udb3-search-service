<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use Psr\Log\LoggerInterface;

// This array logger makes it easy to also check the order of method calls.
// Could be moved to separate library for reuse on other projects.
class SimpleArrayLogger implements LoggerInterface
{
    private $logs = [];

    public function emergency($message, array $context = array())
    {
        $this->logs[] = ["emergency", $message, $context];
    }

    public function alert($message, array $context = array())
    {
        $this->logs[] = ["alert", $message, $context];
    }

    public function critical($message, array $context = array())
    {
        $this->logs[] = ["critical", $message, $context];
    }

    public function error($message, array $context = array())
    {
        $this->logs[] = ["error", $message, $context];
    }

    public function warning($message, array $context = array())
    {
        $this->logs[] = ["warning", $message, $context];
    }

    public function notice($message, array $context = array())
    {
        $this->logs[] = ["notice", $message, $context];
    }

    public function info($message, array $context = array())
    {
        $this->logs[] = ["info", $message, $context];
    }

    public function debug($message, array $context = array())
    {
        $this->logs[] = ["debug", $message, $context];
    }

    public function log($level, $message, array $context = array())
    {
        $this->logs[] = [$level, $message, $context];
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
