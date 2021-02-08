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
     * @return null|string
     */
    public function firstPage()
    {
        if ($this->pageUrlGenerator) {
            return $this->pageUrlGenerator->urlForPage($this->firstPageNumber);
        }

        return null;
    }

    /**
     * @return int
     */
    private function lastPageNumber()
    {
        $lastPageNumber = (int) ceil($this->totalItems / $this->itemsPerPage);

        if ($this->firstPageNumber === 0) {
            --$lastPageNumber;
        };

        return $lastPageNumber;
    }

    /**
     * @return string
     */
    public function lastPage()
    {
        if ($this->pageUrlGenerator) {
            return $this->pageUrlGenerator->urlForPage(
                $this->lastPageNumber()
            );
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function nextPage()
    {
        if ($this->pageUrlGenerator && $this->lastPageNumber() > $this->pageNumber) {
            return $this->pageUrlGenerator->urlForPage($this->pageNumber + 1);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function previousPage()
    {
        if ($this->pageUrlGenerator && $this->pageNumber > $this->firstPageNumber) {
            return $this->pageUrlGenerator->urlForPage($this->pageNumber - 1);
        }

        return null;
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
     * @return PageUrlGenerator|null
     */
    public function getPageUrlGenerator()
    {
        return $this->pageUrlGenerator;
    }

    /**
     * @param PageUrlGenerator $pageUrlGenerator
     * @return PagedCollection
     */
    public function withPageUrlGenerator(PageUrlGenerator $pageUrlGenerator)
    {
        $c = clone $this;
        $c->pageUrlGenerator = $pageUrlGenerator;
        return $c;
    }

    /**
     * @return bool
     */
    public function usesZeroBasedNumbering()
    {
        return $this->firstPageNumber === 0;
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
            'firstPage' => $this->firstPage(),
            'lastPage' => $this->lastPage(),
            'previousPage' => $this->previousPage(),
            'nextPage' => $this->nextPage(),
        ];

        return array_filter($data, function ($item) {
            return null !== $item;
        });
    }
}
