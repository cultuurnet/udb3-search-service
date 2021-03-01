<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Search\JsonDocument\JsonDocumentIndexServiceInterface;

abstract class AbstractSearchProjector implements EventListenerInterface
{
    /**
     * @var JsonDocumentIndexServiceInterface
     */
    private $indexService;


    public function __construct(
        JsonDocumentIndexServiceInterface $indexService
    ) {
        $this->indexService = $indexService;
    }


    public function handle(DomainMessage $domainMessage)
    {
        $handlers = $this->getEventHandlers();

        $payload = $domainMessage->getPayload();
        $payloadType = get_class($payload);

        if (array_key_exists($payloadType, $handlers) &&
            method_exists($this, $handlers[$payloadType])) {
            $handler = $handlers[$payloadType];
            $this->{$handler}($payload);
        }
    }

    /**
     * @return array
     */
    abstract protected function getEventHandlers();

    /**
     * @return JsonDocumentIndexServiceInterface
     */
    protected function getIndexService()
    {
        return $this->indexService;
    }
}
