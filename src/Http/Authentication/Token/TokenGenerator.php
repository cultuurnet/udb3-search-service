<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Token;

interface TokenGenerator
{
    public function managementToken(): Token;

    public function loginToken(): Token;
}
