<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\HandlerInterface;
use Sentry\State\Scope;
use function Sentry\withScope;

/**
 * @see https://github.com/getsentry/sentry-php/blob/master/UPGRADE-3.0.md
 */
final class SentryHandlerScopeDecorator implements HandlerInterface
{
    /**
     * @var HandlerInterface
     */
    private $decoratedHandler;

    /**
     * @var ApiKey
     */
    private $apiKey;

    /**
     * @var bool
     */
    private $console;

    private function __construct(HandlerInterface $decoratedHandler, ApiKey $apiKey, bool $console)
    {
        $this->decoratedHandler = $decoratedHandler;
        $this->apiKey = $apiKey;
        $this->console = $console;
    }

    public static function forWeb(HandlerInterface $decoratedHandler, ?ApiKey $apiKey): self
    {
        $apiKey = $apiKey ?? new ApiKey('null');
        return new self($decoratedHandler, $apiKey, false);
    }

    public static function forCli(HandlerInterface $decoratedHandler): self
    {
        return new self($decoratedHandler, new ApiKey('null'), true);
    }

    public function handle(array $record): bool
    {
        $result = false;

        withScope(function (Scope $scope) use ($record, &$result): void {
            $scope->setTags(
                [
                    'api_key' => $this->apiKey->toString(),
                    'runtime.env' => $this->console ? 'cli' : 'web',
                ]
            );

            $result = $this->decoratedHandler->handle($record);
        });

        return $result;
    }

    public function handleBatch(array $records): void
    {
        $this->decoratedHandler->handleBatch($records);
    }

    public function isHandling(array $record): bool
    {
        return $this->decoratedHandler->isHandling($record);
    }

    public function pushProcessor($callback): self
    {
        $this->decoratedHandler->pushProcessor($callback);
        return $this;
    }

    public function popProcessor(): callable
    {
        return $this->decoratedHandler->popProcessor();
    }

    public function setFormatter(FormatterInterface $formatter): self
    {
        $this->decoratedHandler->setFormatter($formatter);
        return $this;
    }

    public function getFormatter(): FormatterInterface
    {
        return $this->decoratedHandler->getFormatter();
    }
}
