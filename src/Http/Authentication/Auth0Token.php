<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use DateTimeImmutable;

final class Auth0Token
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var DateTimeImmutable
     */
    private $issuedAt;

    /**
     * @var int
     */
    private $expiresIn;

    public function __construct(string $token, DateTimeImmutable $issuedAt, int $expiresIn)
    {
        $this->token = $token;
        $this->issuedAt = $issuedAt;
        $this->expiresIn = $expiresIn;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getIssuedAt(): DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->issuedAt->modify('+' . $this->expiresIn . 'seconds');
    }
}
