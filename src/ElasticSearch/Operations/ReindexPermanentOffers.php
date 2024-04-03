<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Operations;

final class ReindexPermanentOffers extends AbstractReindexUDB3CoreOperation
{
    public function getQueryArray(): array
    {
        return [
            'match' => [
                'calendarType' => 'permanent',
            ],
        ];
    }
}
