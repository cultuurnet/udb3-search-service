<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

class ReindexUDB3Core extends AbstractReindexUDB3CoreOperation
{
    /**
     * @return array
     */
    public function getQueryArray()
    {
        return [
            // @see https://github.com/elastic/elasticsearch-php/issues/495
            'match_all' => (object) [],
        ];
    }
}
