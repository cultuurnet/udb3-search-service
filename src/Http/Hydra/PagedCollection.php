<?php

namespace CultuurNet\UDB3\Search\Http\Hydra;

use JsonSerializable;

class PagedCollection implements JsonSerializable
{
    /**
     * @var int
     */
    private $pageNumber;

    /**
     * @var int
     */
    private $itemsPerPage;

    /**
     * @var array
     */
    private $members;

    /**
     * @var int
     */
    private $totalItems;

    /**
     * @var int
     */
    private $firstPageNumber = 1;

    /**
     * @param int $pageNumber
     * @param int $itemsPerPage
     * @param array $members
     * @param int $totalItems
     */
    public function __construct(
        $pageNumber,
        $itemsPerPage,
        array $members,
        $totalItems
    ) {
        $this->setPageNumber($pageNumber);
        $this->setItemsPerpage($itemsPerPage);
        $this->members = $members;
        $this->setTotalItems($totalItems);
        $this->setZeroBasedNumbering(false);
    }

    /**
     * @param bool $enable
     *   enable or disable zero based numbering
     */
    private function setZeroBasedNumbering($enable)
    {
        $this->firstPageNumber = $enable ? 0 : 1;
    }

    private function setPageNumber($pageNumber)
    {
        if (!is_int($pageNumber)) {
            throw new \InvalidArgumentException(
                'pageNumber should be an integer, got ' . gettype($pageNumber)
            );
        }
        $this->pageNumber = $pageNumber;
    }

    /**
     * @return int
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * @param int $totalItems
     */
    private function setTotalItems($totalItems)
    {
        if (!is_int($totalItems)) {
            throw new \InvalidArgumentException(
                'totalItems should be an integer, got ' . gettype($totalItems)
            );
        }
        $this->totalItems = $totalItems;
    }

    /**
     * @return int
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }

    /**
     * @param int $itemsPerPage
     */
    private function setItemsPerPage($itemsPerPage)
    {
        if (!is_int($itemsPerPage)) {
            throw new \InvalidArgumentException(
                'totalItems should be an integer, got ' . gettype($itemsPerPage)
            );
        }
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->itemsPerPage;
    }

    /**
     * @return array
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @param array $members
     * @return PagedCollection
     */
    public function withMembers(array $members)
    {
        $c = clone $this;
        $c->members = $members;
        return $c;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $data = [
            '@context' => 'http://www.w3.org/ns/hydra/context.jsonld',
            '@type' => 'PagedCollection',
            'itemsPerPage' => $this->getItemsPerPage(),
            'totalItems' => $this->getTotalItems(),
            'member' => $this->getMembers(),
        ];

        return array_filter($data, function ($item) {
            return null !== $item;
        });
    }
}
