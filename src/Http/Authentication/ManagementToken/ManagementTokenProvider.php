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

        if ($token === null || $this->isExpired($token)) {
            $token = $this->tokenGenerator->newToken();
            $this->tokenRepository->set($token);
        }

        return $token->getToken();
    }

    private function isExpired(ManagementToken $token): bool
    {
        return (new DateTime())->modify('+5 minutes') > $token->getExpiresAt();
    }
}
