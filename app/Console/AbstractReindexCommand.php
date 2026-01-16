<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\Console;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\BulkIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\IndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\IndexationStrategy\MutableIndexationStrategy;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\AbstractReindexUDB3CoreOperation;
use Elastic\Elasticsearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractReindexCommand extends AbstractElasticSearchCommand
{
    private string $readIndexName;

    private string $scrollTtl;

    private int $scrollSize;

    private int $bulkThreshold;

    private EventBus $eventBus;

    private IndexationStrategy $indexationStrategy;

    public function __construct(
        Client $client,
        string $readIndexName,
        EventBus $eventBus,
        IndexationStrategy $indexationStrategy,
        string $scrollTtl = '1m',
        int $bulkThreshold = 10,
        int $scrollSize = 50
    ) {
        parent::__construct($client);
        $this->readIndexName = $readIndexName;
        $this->scrollTtl = $scrollTtl;
        $this->scrollSize = $scrollSize;
        $this->bulkThreshold = $bulkThreshold;
        $this->eventBus = $eventBus;
        $this->indexationStrategy = $indexationStrategy;
    }

    protected function runOperation(
        InputInterface $input,
        OutputInterface $output,
        AbstractReindexUDB3CoreOperation $operation
    ): void {
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
