<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Factory;

use CultuurNet\UDB3\SearchService\Error\ApiExceptionHandler;
use CultuurNet\UDB3\SearchService\Error\ErrorLoggerHandler;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Psr\Log\LoggerInterface;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

final class ErrorHandlerFactory
{
    public static function forWeb(LoggerInterface $logger): Run
    {
        $whoops = new Run();
        $whoops->sendHttpCode(false);
        $whoops->prependHandler(new ApiExceptionHandler(new SapiStreamEmitter()));
        $whoops->prependHandler(new ErrorLoggerHandler($logger));
        return $whoops;
    }

    public static function forCli(LoggerInterface $logger): Run
    {
        $whoops = new Run();
        $whoops->prependHandler(new PlainTextHandler());
        $whoops->prependHandler(new ErrorLoggerHandler($logger));
        return $whoops;
    }

    public static function forWebDebug(LoggerInterface $logger): Run
    {
        $whoops = new Run();
        $whoops->prependHandler(new PrettyPageHandler());
        $whoops->prependHandler(new ErrorLoggerHandler($logger));
        return $whoops;
    }
}
