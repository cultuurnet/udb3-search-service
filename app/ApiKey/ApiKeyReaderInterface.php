<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\ApiKey;

use CultuurNet\UDB3\Search\Http\ApiRequestInterface;

interface ApiKeyReaderInterface
{
    public function read(ApiRequestInterface $request);
}
