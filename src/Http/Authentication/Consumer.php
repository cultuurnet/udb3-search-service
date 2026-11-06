<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

final class Consumer
{
    public function __construct(
        private readonly ?string $id,
        private readonly ?string $defaultQuery
    ) {
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDefaultQuery(): ?string
    {
        return $this->defaultQuery;
    }
}
