<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\BroadwayAMQP\ConsumerInterface;
use CultuurNet\UDB3\SearchService\Factory\ConfigFactory;
use CultuurNet\UDB3\SearchService\Factory\ContainerFactory;
use League\Container\Container;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeCommand extends Command
{
    /**
     * @var ConsumerInterface
     */
    private $consumer;

    public function __construct(string $name, ConsumerInterface $consumer)
    {
        parent::__construct($name);

        $this->consumer = $consumer;
    }

    private function handleSignal(OutputInterface $output, $signal)
    {
        $output->writeln('Signal received, halting.');
        exit;
    }

    private function registerSignalHandlers(OutputInterface $output)
    {
        $handler = function ($signal) use ($output) {
            $this->handleSignal($output, $signal);
        };

        foreach ([SIGINT, SIGTERM, SIGQUIT] as $signal) {
            pcntl_signal($signal, $handler);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $this->registerSignalHandlers($output);

        $output->writeln('Connecting...');
        $channel = $this->getChannel();
        $output->writeln('Connected. Listening for incoming messages...');

        while (count($channel->callbacks) > 0) {
            pcntl_signal_dispatch();

            try {
                $channel->wait(null, true, 4);
            } catch (AMQPTimeoutException $e) {
                // Ignore this one.
            }
        }

        return 0;
    }

    /**
     * @return AMQPChannel
     */
    protected function getChannel()
    {
        /** @var ConsumerInterface $consumer */
        $consumer = $this->consumer;
        $channel = $consumer->getChannel();

        if (!$channel instanceof AMQPChannel) {
            throw new RuntimeException(
                'The consumer channel is not of the expected type AMQPChannel'
            );
        }

        return $channel;
    }

    /**
     * @return Container
     * @TODO: Inject these dependencies (this is a temp fix)
     */
    private function getSilexApplication(): Container
    {
        return ContainerFactory::forCli(ConfigFactory::create(__DIR__.'/../../'));
    }
}
