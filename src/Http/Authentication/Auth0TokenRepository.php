<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

interface Auth0TokenRepository
{
    public function get(): ?ManagementToken;

    public function set(ManagementToken $token): void;
}
