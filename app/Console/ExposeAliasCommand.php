<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\GetAliases;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ExposeAliasCommand extends AbstractElasticSearchCommand
{
    public function configure(): void
    {
        $this
            ->setName('es:expose:alias')
            ->setDescription('Outputs the current Elasticsearch aliases and the index each one points to, as JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // A NullLogger keeps stdout to just the JSON line, since callers parse it directly.
        $operation = new GetAliases(
            $this->getElasticSearchClient(),
            new NullLogger()
        );

        $output->writeln((string) json_encode($operation->run(), JSON_THROW_ON_ERROR));

        return 0;
    }
}
