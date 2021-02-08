<?php
/**
 * @file
 */

namespace CultuurNet\Hydra;

interface PageUrlGenerator
{
    /**
     * @param int $pageNumber
     * @return string
     */
    public function urlForPage($pageNumber);
}
