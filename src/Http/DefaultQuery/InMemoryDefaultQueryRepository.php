<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\DefaultQuery;

final class InMemoryDefaultQueryRepository implements DefaultQueryRepository
{
    private array $defaultQueryConfig;

    public function __construct(array $defaultQueryConfig)
    {
        $this->defaultQueryConfig = $defaultQueryConfig;
    }

    public function getByApiKey(string $apiKey): ?string
    {
        return $this->defaultQueryConfig[$apiKey] ?? null;
    }
}
