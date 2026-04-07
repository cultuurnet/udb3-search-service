<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Taxonomy;

interface TaxonomyApiClient
{
    public function getTypes(): array;

    public function getThemes(): array;

    public function getFacilities(): array;
}
