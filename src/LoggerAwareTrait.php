<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{
    private LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
