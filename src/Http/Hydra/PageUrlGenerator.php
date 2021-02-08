<?php

namespace CultuurNet\UDB3\Search\Http\Hydra;

interface PageUrlGenerator
{
    /**
     * @param int $pageNumber
     * @return string
     */
    public function urlForPage($pageNumber);
}
