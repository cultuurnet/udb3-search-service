<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\CreateUrlDomainAnalyzer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateUrlDomainAnalyzerCommand extends AbstractElasticSearchCommand
{
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName('url-domain-analyzer:create')
            ->setDescription('Creates or updates the template for an exact url-domain match analyzer.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new CreateUrlDomainAnalyzer(
            $this->getElasticSearchClient(),
            $this->getLogger($output)
        );

        $operation->run();

        return 0;
    }
}
