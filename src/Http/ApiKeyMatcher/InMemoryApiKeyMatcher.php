<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\ApiKeyMatcher;

final class InMemoryApiKeyMatcher implements ApiKeyMatcher
{
    private array $apiKeyMatcher;

    public function __construct(array $apiKeyMatcher)
    {
        $this->apiKeyMatcher = $apiKeyMatcher;
    }

    public function getClientId(string $apiKey): ?string
    {
        return $this->apiKeyMatcher[$apiKey] ?? null;
    }
}
