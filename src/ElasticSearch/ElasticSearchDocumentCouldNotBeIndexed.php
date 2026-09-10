<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use RuntimeException;
use Throwable;

final class ElasticSearchDocumentCouldNotBeIndexed extends RuntimeException
{
    public static function forDocument(string $id, Throwable $previous): self
    {
        return new self(
            sprintf(
                'Document %s could not be indexed in ElasticSearch: %s',
                $id,
                $previous->getMessage()
            ),
            0,
            $previous
        );
    }
}
