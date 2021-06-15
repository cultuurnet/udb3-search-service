<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

interface Auth0TokenRepository
{
    public function get(): ?string;

    public function set(string $token): void;
}
