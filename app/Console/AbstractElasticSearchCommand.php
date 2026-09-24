<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Elasticsearch\Client;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractElasticSearchCommand extends AbstractCommand
{
    private Client $client;

    public function __construct(Client $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    protected function getElasticSearchClient(): Client
    {
        return $this->client;
    }

    protected function getLogger(OutputInterface $output): ConsoleLogger
    {
        $output = clone $output;
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        return new ConsoleLogger($output);
    }
}
