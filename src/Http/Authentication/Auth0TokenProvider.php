<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use DateTime;

final class Auth0TokenProvider
{
    /**
     * @var Auth0TokenRepository
     */
    private $auth0TokenRepository;

    /**
     * @var Auth0Client
     */
    private $auth0Client;

    public function __construct(Auth0TokenRepository $auth0TokenRepository, Auth0Client $auth0Client)
    {
        $this->auth0TokenRepository = $auth0TokenRepository;
        $this->auth0Client = $auth0Client;
    }

    public function get(): Auth0Token
    {
        $token = $this->auth0TokenRepository->get();

        if ($token === null || $this->expiresWithin($token, '+5 minutes')) {
            $token = $this->auth0Client->getToken();
            $this->auth0TokenRepository->set($token);
        }

        return $token;
    }

    private function expiresWithin(Auth0Token $token, string $offset): bool
    {
        return (new DateTime())->modify($offset) > $token->getExpiresAt();
    }
}
