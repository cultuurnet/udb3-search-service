<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\ManagementToken;

interface ManagementTokenGenerator
{
    public function newToken(): ManagementToken;
}
