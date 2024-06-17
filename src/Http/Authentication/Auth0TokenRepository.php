<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementToken;

interface Auth0TokenRepository
{
    public function get(): ?ManagementToken;

    public function set(ManagementToken $token): void;
}
