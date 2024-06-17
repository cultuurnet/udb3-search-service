<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementToken;
use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementTokenRepository;
use DateTime;

final class Auth0TokenProvider
{
    private ManagementTokenRepository $auth0TokenRepository;

    private Auth0Client $auth0Client;

    public function __construct(ManagementTokenRepository $auth0TokenRepository, Auth0Client $auth0Client)
    {
        $this->auth0TokenRepository = $auth0TokenRepository;
        $this->auth0Client = $auth0Client;
    }

    public function get(): ManagementToken
    {
        $token = $this->auth0TokenRepository->get();

        if ($token === null || $this->expiresWithin($token, '+5 minutes')) {
            $token = $this->auth0Client->getToken();
            $this->auth0TokenRepository->set($token);
        }

        return $token;
    }

    private function expiresWithin(ManagementToken $token, string $offset): bool
    {
        return (new DateTime())->modify($offset) > $token->getExpiresAt();
    }
}
