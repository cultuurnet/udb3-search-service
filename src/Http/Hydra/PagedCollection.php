<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Hydra;

use JsonSerializable;

final class PagedCollection implements JsonSerializable
{
    private int $pageNumber;


    private int $itemsPerPage;

    private array $members;


    private int $totalItems;

    public function __construct(
        int $pageNumber,
        int $itemsPerPage,
        array $members,
        int $totalItems
    ) {
        $this->setPageNumber($pageNumber);
        $this->setItemsPerpage($itemsPerPage);
        $this->members = $members;
        $this->setTotalItems($totalItems);
    }

    private function setPageNumber(int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    private function setTotalItems(int $totalItems): void
    {
        $this->totalItems = $totalItems;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    private function setItemsPerPage(int $itemsPerPage): void
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public function withMembers(array $members): PagedCollection
    {
        $c = clone $this;
        $c->members = $members;
        return $c;
    }

    public function jsonSerialize(): array
    {
        $data = [
            '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
            '@type' => 'PagedCollection',
            'itemsPerPage' => $this->getItemsPerPage(),
            'totalItems' => $this->getTotalItems(),
            'member' => $this->getMembers(),
        ];

        return array_filter($data, static fn ($item): bool => null !== $item);
    }
}
