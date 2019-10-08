<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\ApiKey;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;

final class ApiKeyReaderServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        ApiKeyReaderInterface::class,
    ];

    public function register()
    {
        $this->add(
            ApiKeyReaderInterface::class,
            function () {
                $queryReader = new QueryParameterApiKeyReader('apiKey');
                $headerReader = new CustomHeaderApiKeyReader('X-Api-Key');

                return new ApiKeyReaderSymfonyAdapter(
                    new CompositeApiKeyReader(
                        $queryReader,
                        $headerReader
                    )
                );
            }
        );
    }
}
