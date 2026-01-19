<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\ApiKeyMatcher;

final class InMemoryApiKeyMatcher implements ApiKeyMatcher
{
    private array $apiKeyToClientIdMap;

    public function __construct(array $apiKeyToClientIdMap)
    {
        $this->apiKeyToClientIdMap = $apiKeyToClientIdMap;
    }

    public function getClientId(string $apiKey): ?string
    {
        return $this->apiKeyToClientIdMap[$apiKey] ?? null;
    }
}
