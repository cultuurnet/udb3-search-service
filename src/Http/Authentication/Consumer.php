<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

final class Consumer
{
    private ?string $id;

    private ?string $defaultQuery;

    private bool $hasBoaAccess;

    private ?string $userId;

    public function __construct(?string $id, ?string $defaultQuery, bool $hasBoaAccess = false, ?string $userId = null)
    {
        $this->id = $id;
        $this->defaultQuery = $defaultQuery;
        $this->hasBoaAccess = $hasBoaAccess;
        $this->userId = $userId;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDefaultQuery(): ?string
    {
        return $this->defaultQuery;
    }

    public function hasBoaAccess(): bool
    {
        return $this->hasBoaAccess;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }
}
