<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

final class Consumer
{
    /**
     * @var ?string
     */
    private $id;

    /**
     * @var ?string
     */
    private $defaultQuery;

    public function __construct(?string $id, ?string $defaultQuery)
    {
        $this->id = $id;
        $this->defaultQuery = $defaultQuery;
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
