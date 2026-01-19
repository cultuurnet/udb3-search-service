<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\ApiKeyMatcher;

interface ApiKeyMatcher
{
    public function getClientId(string $apiKey): ?string;
}
