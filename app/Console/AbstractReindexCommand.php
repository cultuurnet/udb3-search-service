<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\BulkIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\IndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\AbstractReindexUDB3CoreOperation;
use Elasticsearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractReindexCommand extends AbstractElasticSearchCommand
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
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var IndexationStrategy
     */
    private $indexationStrategy;

    /**
     * @param string $readIndexName
     * @param string $scrollTtl
     * @param int $scrollSize
     * @param int $bulkThreshold
     */
    public function __construct(
        Client $client,
        $readIndexName,
        EventBus $eventBus,
        IndexationStrategy $indexationStrategy,
        $scrollTtl = '1m',
        $scrollSize = 50,
        $bulkThreshold = 10
    ) {
        parent::__construct($client);
        $this->readIndexName = $readIndexName;
        $this->scrollTtl = $scrollTtl;
        $this->scrollSize = $scrollSize;
        $this->bulkThreshold = $bulkThreshold;
        $this->eventBus = $eventBus;
        $this->indexationStrategy = $indexationStrategy;
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
            $bulkIndexationStrategy->finish();
        }
    }

    protected function getEventBus(): EventBus
    {
        return $this->eventBus;
    }

    protected function getIndexationStrategy(): IndexationStrategy
    {
        return $this->indexationStrategy;
    }

    protected function getScrollTtl(): string
    {
        return $this->scrollTtl;
    }

    protected function getScrollSize(): int
    {
        return $this->scrollSize;
    }
}
