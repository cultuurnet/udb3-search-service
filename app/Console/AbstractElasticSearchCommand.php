<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\ElasticSearchClientInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractElasticSearchCommand extends AbstractCommand
{
    private ElasticSearchClientInterface $client;

    public function __construct(ElasticSearchClientInterface $client)
    {
        parent::__construct();
        $this->client = $client;
    }

    protected function getElasticSearchClient(): ElasticSearchClientInterface
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
