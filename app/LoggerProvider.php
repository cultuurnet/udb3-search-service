<?php declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerProvider extends BaseServiceProvider
{
    protected $provides = [
        'logger.amqp.udb3_consumer',
    ];

    /**
     * Use the register method to register items with the container via the
     * protected $this->leagueContainer property or the `getLeagueContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->add(
            'logger.amqp.udb3_consumer',
            function () {
                $logger = new Logger('amqp.udb3_publisher');
                $logger->pushHandler(new StreamHandler('php://stdout'));

                $logFileHandler = new StreamHandler(
                    __DIR__ . '/../log/amqp.log',
                    Logger::DEBUG
                );
                $logger->pushHandler($logFileHandler);

                return $logger;
            }
        );
    }
}
