<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\ManagementToken;

use DateTime;

final class ManagementTokenProvider
{
    private ManagementTokenGenerator $tokenGenerator;

    private ManagementTokenRepository $tokenRepository;

    public function __construct(
        ManagementTokenGenerator $tokenGenerator,
        ManagementTokenRepository $tokenRepository
    ) {
        $this->tokenGenerator = $tokenGenerator;
        $this->tokenRepository = $tokenRepository;
    }

    public function token(): string
    {
        $token = $this->tokenRepository->get();

        if ($token === null || $this->expiresWithin($token, '+5 minutes')) {
            $token = $this->tokenGenerator->newToken();
            $this->tokenRepository->set($token);
        }

        return $token->getToken();
    }

    private function expiresWithin(ManagementToken $token, string $offset): bool
    {
        return (new DateTime())->modify($offset) > $token->getExpiresAt();
    }
}
