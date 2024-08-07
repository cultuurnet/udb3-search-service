<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use Broadway\EventHandling\SimpleEventBus as BroadwaySimpleEventBus;

/**
 * Decorator of Broadway's SimpleEventBus with a configurable callback to be
 * executed before the first message is published. This callback can be used to
 * subscribe listeners.
 */
final class SimpleEventBus implements EventBus
{
    private bool $first = true;
    private BroadwaySimpleEventBus $eventBus;

    /**
     * @var null|callable
     */
    private $beforeFirstPublicationCallback;

    public function __construct()
    {
        $this->eventBus = new BroadwaySimpleEventBus();
    }

    public function subscribe(EventListener $eventListener): void
    {
        $this->eventBus->subscribe($eventListener);
    }

    public function beforeFirstPublication(callable $callback): void
    {
        $this->beforeFirstPublicationCallback = $callback;
    }

    private function callBeforeFirstPublicationCallback(): void
    {
        if ($this->beforeFirstPublicationCallback) {
            $callback = $this->beforeFirstPublicationCallback;
            $callback($this->eventBus);
        }
    }

    public function publish(DomainEventStream $domainMessages): void
    {
        if ($this->first) {
            $this->first = false;
            $this->callBeforeFirstPublicationCallback();
        }

        $this->eventBus->publish($domainMessages);
    }
}
