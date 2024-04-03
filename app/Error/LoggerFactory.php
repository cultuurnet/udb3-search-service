<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use Monolog\Handler\GroupHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;

final class LoggerFactory
{
    /**
     * @var Logger[]
     */
    private static $loggers;

    /**
     * @var StreamHandler[]
     */
    private static $streamHandlers = [];

    public static function create(
        ContainerInterface $container,
        LoggerName $name,
        array $extraHandlers = []
    ): Logger {
        $loggerName = $name->getLoggerName();
        $fileNameWithoutSuffix = $name->getFileNameWithoutSuffix();

        if (!isset(self::$loggers[$loggerName])) {
            self::$loggers[$loggerName] = new Logger($loggerName);
            self::$loggers[$loggerName]->pushProcessor(new PsrLogMessageProcessor());

            $streamHandler = self::getStreamHandler($fileNameWithoutSuffix);
            $sentryHandler = $container->get(SentryHandlerScopeDecorator::class);

            $handlers = new GroupHandler(array_merge([$streamHandler, $sentryHandler], $extraHandlers));
            self::$loggers[$loggerName]->pushHandler($handlers);
        }

        return self::$loggers[$loggerName];
    }

    private static function getStreamHandler(string $name): StreamHandler
    {
        if (!isset(self::$streamHandlers[$name])) {
            self::$streamHandlers[$name] = new StreamHandler(__DIR__ . '/../../log/' . $name . '.log', Logger::DEBUG);
            self::$streamHandlers[$name]->pushProcessor(new ContextExceptionConverterProcessor());
        }

        return self::$streamHandlers[$name];
    }
}
