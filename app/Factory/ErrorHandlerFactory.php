<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use CultuurNet\UDB3\SearchService\Error\ApiExceptionHandler;
use CultuurNet\UDB3\SearchService\Error\SentryExceptionHandler;
use Sentry\State\HubInterface;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class ErrorHandlerFactory
{
    public static function forWeb(HubInterface $hubInterface, ?ApiKey $apiKey, bool $isDebugEnvironment): Run
    {
        $whoops = new Run();
        self::prependWebHandler($whoops, $isDebugEnvironment);
        $whoops->prependHandler(new SentryExceptionHandler($hubInterface, $apiKey, false));
        return $whoops;
    }

    public static function forCli(HubInterface $hubInterface): Run
    {
        $whoops = new Run();
        $whoops->prependHandler(new PlainTextHandler());
        $whoops->prependHandler(new SentryExceptionHandler($hubInterface, null, true));
        return $whoops;
    }

    private static function prependWebHandler(Run $whoops, bool $isDebugEnvironment): void
    {
        if ($isDebugEnvironment === true) {
            $whoops->prependHandler(new PrettyPageHandler());
            return;
        }

        $whoops->prependHandler(new ApiExceptionHandler(new SapiStreamEmitter()));
    }
}
