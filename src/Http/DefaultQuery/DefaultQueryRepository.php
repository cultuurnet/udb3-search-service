<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\DefaultQuery;

interface DefaultQueryRepository
{
    public function getByApiKey(string $apiKey): ?string;
}
