<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

final class Consumer
{
    private ?string $id;

    private ?string $defaultQuery;

    private bool $hasBoaAccess;

    public function __construct(?string $id, ?string $defaultQuery, bool $hasBoaAccess = false)
    {
        $this->id = $id;
        $this->defaultQuery = $defaultQuery;
        $this->hasBoaAccess = $hasBoaAccess;
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
}
