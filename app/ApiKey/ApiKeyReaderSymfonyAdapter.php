<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\ApiKey;

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface as SymfonyApiKeyReaderInterface;
use CultuurNet\UDB3\Search\Http\ApiRequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class ApiKeyReaderSymfonyAdapter implements ApiKeyReaderInterface
{
    /**
     * @var SymfonyApiKeyReaderInterface
     */
    private $apiKeyReader;

    public function __construct(SymfonyApiKeyReaderInterface $apiKeyReader)
    {
        $this->apiKeyReader = $apiKeyReader;
    }
    
    public function read(ApiRequestInterface $request)
    {
        return $this->apiKeyReader->read(
            SymfonyRequest::create(
                $request->getUri(),
                $request->getMethod(),
                $request->getQueryParams()
            )
        );
    }
}
