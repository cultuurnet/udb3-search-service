<?php

namespace CultuurNet\UDB3\SearchService\Console;

use CultuurNet\UDB3\Search\ElasticSearch\Operations\IndexRegions;
use Elasticsearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class IndexRegionsCommand extends AbstractElasticSearchCommand
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
     * @param Client $client
     * @param string $indexName
     * @param string $pathToScan
     * @param string $fileNameRegex
     */
    public function __construct(Client $client, $indexName, $pathToScan, $fileNameRegex = '*.json')
    {
        parent::__construct($client);
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
            ->setName('geoshapes:index-regions')
            ->setDescription('Indexes all region documents from a given directory.');
    }
    
    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new IndexRegions(
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
