<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Token;

interface ManagementTokenRepository
{
    public function get(): ?Token;

    public function set(Token $token): void;
}
