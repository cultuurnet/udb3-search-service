<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

interface Auth0TokenRepository
{
    public function get(): ?Auth0Token;

    public function set(Auth0Token $token): void;
}
