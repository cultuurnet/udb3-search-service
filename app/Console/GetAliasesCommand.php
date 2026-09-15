<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\GetAliases;
use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class GetAliasesCommand extends AbstractElasticSearchCommand
{
    private LoggerInterface $logger;

    public function __construct(Client $client, LoggerInterface $logger)
    {
        parent::__construct($client);
        $this->logger = $logger;
    }

    public function configure(): void
    {
        $this
            ->setName('elasticsearch:aliases')
            ->setDescription('Outputs the current Elasticsearch aliases and the index each one points to, as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // stdout must stay a single machine-readable JSON line for callers that parse it directly,
        // so operation logging goes to the cli log file instead of the console.
        $operation = new GetAliases(
            $this->getElasticSearchClient(),
            $this->logger
        );

        try {
            $aliases = $operation->run();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            return 1;
        }

        $output->writeln((string) json_encode($aliases, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT));

        return 0;
    }
}
