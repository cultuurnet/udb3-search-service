<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

interface ConsumerResolver
{
    public function getStatus(string $apiKey): string;
    public function getDefaultQuery(string $apiKey): ?string;
}
