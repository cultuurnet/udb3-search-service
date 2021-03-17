<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use CultuurNet\UDB3\SearchService\Error\LoggerFactory;
use CultuurNet\UDB3\SearchService\Error\LoggerName;
use Monolog\Handler\StreamHandler;

final class LoggerProvider extends BaseServiceProvider
{
    protected $provides = [
        'logger.amqp.udb3_consumer',
    ];

    public function register(): void
    {
        $this->add(
            'logger.amqp.udb3_consumer',
            function () {
                return LoggerFactory::create(
                    $this->getContainer(),
                    new LoggerName('amqp', 'amqp.udb3_publisher'),
                    [new StreamHandler('php://stdout')]
                );
            }
        );
    }
}
