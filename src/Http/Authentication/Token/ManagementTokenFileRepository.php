<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Token;

use CultuurNet\UDB3\Search\FileReader;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;

final class ManagementTokenFileRepository implements ManagementTokenRepository
{
    private string $fullFilePath;

    public function __construct(string $fullFilePath)
    {
        $this->fullFilePath = $fullFilePath;
    }

    public function get(): ?Token
    {
        if (!file_exists($this->fullFilePath)) {
            return null;
        }

        $tokenAsArray = Json::decodeAssociatively(FileReader::read($this->fullFilePath));

        return new Token(
            $tokenAsArray['token'],
            new DateTimeImmutable($tokenAsArray['issuesAt']),
            $tokenAsArray['expiresIn']
        );
    }

    public function set(Token $token): void
    {
        $tokenAsJson = Json::encode([
            'token' => $token->getToken(),
            'issuesAt' => $token->getIssuedAt()->format(DATE_ATOM),
            'expiresIn' => $token->getExpiresIn(),
        ]);

        file_put_contents($this->fullFilePath, $tokenAsJson, LOCK_EX);
    }
}
