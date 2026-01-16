<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Elastic\Elasticsearch\ClientInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractElasticSearchCommand extends AbstractCommand
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    protected function getElasticSearchClient(): ClientInterface
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
