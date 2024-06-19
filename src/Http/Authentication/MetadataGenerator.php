<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

interface MetadataGenerator
{
    public function get(string $clientId, string $token): ?array;
}
