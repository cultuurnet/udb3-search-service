<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\IndexRegionQueries;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class IndexRegionQueriesCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    private $regionQueryIndexName;

    /**
     * @var string
     */
    private $regionIndexName;

    /**
     * @var string
     */
    private $pathToScan;

    /**
     * @var string
     */
    private $fileNameRegex;

    /**
     * @param string $indexName
     * @param string $regionIndexName
     * @param string $pathToScan
     * @param string $fileNameRegex
     */
    public function __construct($indexName, $regionIndexName, $pathToScan, $fileNameRegex = '*.json')
    {
        parent::__construct();
        $this->regionQueryIndexName = $indexName;
        $this->regionIndexName = $regionIndexName;
        $this->pathToScan = $pathToScan;
        $this->fileNameRegex = $fileNameRegex;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('udb3-core:index-region-queries')
            ->setDescription('Indexes all possible region queries from a given directory with region documents.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new IndexRegionQueries(
            $this->getElasticSearchClient(),
            $this->getLogger($output),
            $this->getFinder()
        );

        $operation->run($this->regionQueryIndexName, $this->regionIndexName, $this->pathToScan, $this->fileNameRegex);
    }

    /**
     * @return Finder
     */
    private function getFinder()
    {
        $app = $this->getSilexApplication();
        return $app['file_finder'];
    }
}
