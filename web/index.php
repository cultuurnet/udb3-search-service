<?php

use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\SearchService\Factory\ConfigFactory;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use CultuurNet\UDB3\SearchService\Factory\ErrorHandlerFactory;
use League\Route\Router;
use Sentry\State\HubInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

require_once __DIR__ . '/../vendor/autoload.php';

$config = ConfigFactory::create(__DIR__ . '/../');

$container = ContainerFactory::forWeb($config);

$apiRequest = new ApiRequest(ServerRequestFactory::createFromGlobals());
$apiKeyReader = $container->get(ApiKeyReaderInterface::class);
$apiKey = $apiKeyReader->read($apiRequest);

$errorHandler = ErrorHandlerFactory::forWeb(
    $container->get(HubInterface::class),
    $apiKey,
    $config->get('debug')
);
$errorHandler->register();

$response = $container->get(Router::class)->dispatch($apiRequest);
(new SapiStreamEmitter())->emit($response);
