<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

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

    public function get(): ?string
    {
        if (!file_exists($this->fullFilePath)) {
            return null;
        }

        return file_get_contents($this->fullFilePath);
    }

    public function set(string $token): void
    {
        file_put_contents($this->fullFilePath, $token, LOCK_EX);
    }
}
