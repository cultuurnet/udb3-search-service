<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

class ReindexPermanentOffers extends AbstractReindexUDB3CoreOperation
{
    /**
     * @return array
     */
    public function getQueryArray()
    {
        return [
            'match' => [
                'calendarType' => 'permanent',
            ],
        ];
    }
}
