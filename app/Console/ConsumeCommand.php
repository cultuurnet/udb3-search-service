<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\AMQP\ConsumerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsumeCommand extends Command
{
    private ConsumerInterface $consumer;

    public function __construct(string $name, ConsumerInterface $consumer)
    {
        parent::__construct($name);
        $this->consumer = $consumer;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->registerSignalHandlers($output);

        $output->writeln('Connecting...');
        $channel = $this->consumer->getChannel();
        $output->writeln('Connected. Listening for incoming messages...');

        while ($channel->is_consuming()) {
            pcntl_signal_dispatch();

            try {
                $channel->wait();
            } catch (AMQPTimeoutException $e) {
                // Ignore this one.
            }
        }

        return 0;
    }

    private function registerSignalHandlers(OutputInterface $output): void
    {
        $handler = static function () use ($output) {
            $output->writeln('Signal received, halting.');
            exit;
        };

        foreach ([SIGINT, SIGTERM, SIGQUIT] as $signal) {
            pcntl_signal($signal, $handler);
        }
    }
}
