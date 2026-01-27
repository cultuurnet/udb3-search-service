<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\ApiKeysMatchedToClientIds;

interface ApiKeysMatchedToClientIds
{
    public function getClientId(string $apiKey): ?string;
}
