<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\SearchService\Error\ApiExceptionHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Zend\HttpHandlerRunner\Emitter\SapiStreamEmitter;

class ErrorHandlerFactory
{

    public static function forWeb(bool $isDebugEnvironment): Run
    {
        $whoops = new Run();
        self::prependWebHandler($whoops, $isDebugEnvironment);
        return $whoops;
    }

    public static function forCli()
    {
        $whoops = new Run();
        $whoops->prependHandler(new PlainTextHandler());
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
