<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

interface ClientIdResolver
{
    public function hasSapiAccess(string $clientId): bool;

    public function hasBoaAccess(string $clientId): bool;
}
