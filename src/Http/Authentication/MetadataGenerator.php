<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use Psr\Log\LoggerInterface;

interface MetadataGenerator
{
    public function get(string $clientId, string $token): ?array;

    public function setLogger(LoggerInterface $logger);
}
