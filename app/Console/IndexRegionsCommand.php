<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\IndexRegions;
use Elasticsearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

final class IndexRegionsCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    private $indexName;

    /**
     * @var string
     */
    private $pathToScan;

    /**
     * @var string
     */
    private $fileNameRegex;

    /**
     * @var Finder
     */
    private $finder;


    public function __construct(
        Client $client,
        Finder $finder,
        string $indexName,
        string $pathToScan,
        string $fileNameRegex = '*.json'
    ) {
        parent::__construct($client);
        $this->indexName = $indexName;
        $this->pathToScan = $pathToScan;
        $this->fileNameRegex = $fileNameRegex;
        $this->finder = $finder;
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setName('geoshapes:index-regions')
            ->setDescription('Indexes all region documents from a given directory.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $operation = new IndexRegions(
            $this->getElasticSearchClient(),
            $this->getLogger($output),
            $this->finder
        );

        $operation->run($this->indexName, $this->pathToScan, $this->fileNameRegex);

        return 0;
    }
}
