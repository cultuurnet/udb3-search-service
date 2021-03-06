<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Search\Language\Language;

interface PredefinedQueryFieldsInterface
{
    /**
     * @param Language ...$languages
     * @return string[]
     */
    public function getPredefinedFields(Language ...$languages): array;
}
