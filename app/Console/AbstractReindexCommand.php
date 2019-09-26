<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\BulkIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\IndexationStrategyInterface;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\AbstractReindexUDB3CoreOperation;
use Elasticsearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractReindexCommand extends AbstractElasticSearchCommand
{
    /**
     * @var string
     */
    private $readIndexName;
    
    /**
     * @var string
     */
    private $scrollTtl;
    
    /**
     * @var int
     */
    private $scrollSize;
    
    /**
     * @var int
     */
    private $bulkThreshold;
    
    /**
     * @param Client $client
     * @param string $readIndexName
     * @param string $scrollTtl
     * @param int $scrollSize
     * @param int $bulkThreshold
     */
    public function __construct(
        Client $client,
        $readIndexName,
        $scrollTtl = '1m',
        $scrollSize = 50,
        $bulkThreshold = 10
    ) {
        parent::__construct($client);
        $this->readIndexName = $readIndexName;
        $this->scrollTtl = $scrollTtl;
        $this->scrollSize = $scrollSize;
        $this->bulkThreshold = $bulkThreshold;
    }
    
    /**
     * @inheritdoc
     */
    protected function runOperation(
        InputInterface $input,
        OutputInterface $output,
        AbstractReindexUDB3CoreOperation $operation
    ) {
        $indexationStrategy = $this->getIndexationStrategy();
        $logger = $this->getLogger($output);
        
        if ($indexationStrategy instanceof MutableIndexationStrategy) {
            $bulkIndexationStrategy = new BulkIndexationStrategy(
                $this->getElasticSearchClient(),
                $logger,
                $this->bulkThreshold
            );
            
            $indexationStrategy->setIndexationStrategy($bulkIndexationStrategy);
        }
        
        $operation->run($this->readIndexName);
        
        if (isset($bulkIndexationStrategy)) {
            $bulkIndexationStrategy->flush();
        }
    }
    
    /**
     * @return EventBusInterface
     */
    protected function getEventBus()
    {
        $app = $this->getSilexApplication();
        return $app['event_bus.udb3-core'];
    }
    
    /**
     * @return IndexationStrategyInterface
     */
    protected function getIndexationStrategy()
    {
        $app = $this->getSilexApplication();
        return $app['elasticsearch_indexation_strategy'];
    }
    
    /**
     * @return string
     */
    protected function getScrollTtl()
    {
        return $this->scrollTtl;
    }
    
    /**
     * @return int
     */
    protected function getScrollSize()
    {
        return $this->scrollSize;
    }
}
