<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\ApiKeysMatchedToClientIds;

final class InMemoryApiKeysMatchedToClientIds implements ApiKeysMatchedToClientIds
{
    public function __construct(readonly array $apiKeysMatchedToClientIds)
    {
    }

    public function getClientId(string $apiKey): ?string
    {
        return $this->apiKeysMatchedToClientIds[$apiKey] ?? null;
    }
}
