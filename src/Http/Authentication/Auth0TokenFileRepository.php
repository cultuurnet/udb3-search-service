<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;

final class Auth0TokenFileRepository implements Auth0TokenRepository
{
    /**
     * @var string
     */
    private $fullFilePath;

    public function __construct(string $fullFilePath)
    {
        $this->fullFilePath = $fullFilePath;
    }

    public function get(): ?Auth0Token
    {
        if (!file_exists($this->fullFilePath)) {
            return null;
        }

        $tokenAsArray = Json::decodeAssociatively(file_get_contents($this->fullFilePath));

        return new Auth0Token(
            $tokenAsArray['token'],
            new DateTimeImmutable($tokenAsArray['issuesAt']),
            $tokenAsArray['expiresIn']
        );
    }

    public function set(Auth0Token $token): void
    {
        $tokenAsJson = Json::encode([
            'token' => $token->getToken(),
            'issuesAt' => $token->getIssuedAt()->format(DATE_ATOM),
            'expiresIn' => $token->getExpiresIn(),
        ]);

        file_put_contents($this->fullFilePath, $tokenAsJson, LOCK_EX);
    }
}
