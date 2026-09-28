<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Monolog\Logger;
use CultuurNet\UDB3\SearchService\Error\LoggerFactory;
use CultuurNet\UDB3\SearchService\Error\LoggerName;
use Monolog\Handler\StreamHandler;

final class AmqpLoggerProvider extends BaseServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            'logger.amqp.udb3',
        ], true);
    }

    public function register(): void
    {
        $this->add(
            'logger.amqp.udb3',
            fn (): Logger => LoggerFactory::create(
                $this->getContainer(),
                LoggerName::forAmqpWorker('udb3'),
                [new StreamHandler('php://stdout')]
            )
        );
    }
}
