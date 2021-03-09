<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Error;

use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKey;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Whoops\Handler\Handler;

final class SentryExceptionHandler extends Handler
{
    /** @var HubInterface */
    private $sentryHub;

    /**
     * @var ApiKey|null
     */
    private $apiKey;

    /**
     * @var bool
     */
    private $console;

    private function __construct(HubInterface $sentryHub, ?ApiKey $apiKey, bool $console)
    {
        $this->sentryHub = $sentryHub;
        $this->apiKey = $apiKey;
        $this->console = $console;
    }

    public function handle(): void
    {
        $this->sentryHub->configureScope(function (Scope $scope) {
            $scope->setTags($this->createTags($this->apiKey, $this->console));
        });

        $exception = $this->getInspector()->getException();
        $this->sentryHub->captureException($exception);
    }

    public static function createForWeb(HubInterface $sentryHub, ?ApiKey $apiKey): SentryExceptionHandler
    {
        return new self($sentryHub, $apiKey, false);
    }

    public static function createForCli(HubInterface $sentryHub): SentryExceptionHandler
    {
        return new self($sentryHub, null, true);
    }

    private function createTags(?ApiKey $apiKey, bool $console): array
    {
        return [
            'api_key' => $apiKey ? $apiKey->toString() : 'null',
            'runtime.env' => $console ? 'cli' : 'web',
        ];
    }
}
