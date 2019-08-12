<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Elasticsearch\Client;
use Knp\Command\Command;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractElasticSearchCommand extends Command
{
    /**
     * @return Client
     */
    protected function getElasticSearchClient()
    {
        $app = $this->getSilexApplication();
        return $app['elasticsearch_client'];
    }

    /**
     * @param OutputInterface $output
     * @return ConsoleLogger
     */
    protected function getLogger(OutputInterface $output)
    {
        $output = clone $output;
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        return new ConsoleLogger($output);
    }
}
