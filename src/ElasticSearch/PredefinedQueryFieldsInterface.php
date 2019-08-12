<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

use CultuurNet\UDB3\Language;

interface PredefinedQueryFieldsInterface
{
    /**
     * @param Language[] $languages
     * @return string[]
     */
    public function getPredefinedFields(Language ...$languages);
}
