<?php

namespace CultuurNet\UDB3\SearchService\Console;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\Search\ElasticSearch\Operations\ReindexUDB3Core;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexUDB3CoreCommand extends AbstractElasticSearchCommand
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
     * @param string $readIndexName
     * @param string $scrollTtl
     * @param int $scrollSize
     */
    public function __construct($readIndexName, $scrollTtl = '1m', $scrollSize = 50)
    {
        parent::__construct();
        $this->readIndexName = $readIndexName;
        $this->scrollTtl = $scrollTtl;
        $this->scrollSize = $scrollSize;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('udb3-core:reindex')
            ->setDescription('Re-indexes existing documents in udb3_core, from the read alias to the write alias.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = new ReindexUDB3Core(
            $this->getElasticSearchClient(),
            $this->getLogger($output),
            $this->getEventBus(),
            $this->scrollTtl,
            $this->scrollSize
        );

        $operation->run($this->readIndexName);
    }

    /**
     * @return EventBusInterface
     */
    private function getEventBus()
    {
        $app = $this->getSilexApplication();
        return $app['event_bus.udb3-core'];
    }
}
