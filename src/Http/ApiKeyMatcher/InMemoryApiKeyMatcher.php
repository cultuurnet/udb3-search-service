<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\ApiKeyMatcher;

final class InMemoryApiKeyMatcher implements ApiKeyMatcher
{
    public function __construct(readonly array $apiKeyToClientIdMap)
    {
    }

    public function getClientId(string $apiKey): ?string
    {
        return $this->apiKeyToClientIdMap[$apiKey] ?? null;
    }
}
