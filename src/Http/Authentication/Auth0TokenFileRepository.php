<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementToken;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;

final class Auth0TokenFileRepository implements Auth0TokenRepository
{
    private string $fullFilePath;

    public function __construct(string $fullFilePath)
    {
        $this->fullFilePath = $fullFilePath;
    }

    public function get(): ?ManagementToken
    {
        if (!file_exists($this->fullFilePath)) {
            return null;
        }

        $tokenAsArray = Json::decodeAssociatively(file_get_contents($this->fullFilePath));

        return new ManagementToken(
            $tokenAsArray['token'],
            new DateTimeImmutable($tokenAsArray['issuesAt']),
            $tokenAsArray['expiresIn']
        );
    }

    public function set(ManagementToken $token): void
    {
        $tokenAsJson = Json::encode([
            'token' => $token->getToken(),
            'issuesAt' => $token->getIssuedAt()->format(DATE_ATOM),
            'expiresIn' => $token->getExpiresIn(),
        ]);

        file_put_contents($this->fullFilePath, $tokenAsJson, LOCK_EX);
    }
}
