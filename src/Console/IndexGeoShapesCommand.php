<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\IndexGeoShapes;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class IndexGeoShapesCommand extends AbstractElasticSearchCommand
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
     * @param string $indexName
     * @param string $pathToScan
     * @param string $fileNameRegex
     */
    public function __construct($indexName, $pathToScan, $fileNameRegex = '*.json')
    {
        parent::__construct();
        $this->indexName = $indexName;
        $this->pathToScan = $pathToScan;
        $this->fileNameRegex = $fileNameRegex;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('geoshapes:index')
            ->setDescription('Indexes all geoshape documents from a given directory.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new IndexGeoShapes(
            $this->getElasticSearchClient(),
            $this->getLogger($output),
            $this->getFinder()
        );

        $operation->run($this->indexName, $this->pathToScan, $this->fileNameRegex);
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
