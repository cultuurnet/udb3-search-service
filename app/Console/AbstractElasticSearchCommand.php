<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Elasticsearch\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractElasticSearchCommand extends Command
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        parent::__construct();
        $this->client = $client;
    }


    /**
     * @return Client
     */
    protected function getElasticSearchClient()
    {
        return $this->client;
    }

    /**
     * @return ConsoleLogger
     */
    protected function getLogger(OutputInterface $output)
    {
        $output = clone $output;
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        return new ConsoleLogger($output);
    }
}
