<?php
declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Broadway\Domain\DomainEventStreamInterface;

class SimpleEventBus extends \Broadway\EventHandling\SimpleEventBus
{
    /**
     * @var bool
     */
    private $first = true;

    /**
     * @var null|callable
     */
    private $beforeFirstPublicationCallback;

    /**
     * @param callable $callback
     */
    public function beforeFirstPublication(callable $callback): void
    {
        $this->beforeFirstPublicationCallback = $callback;
    }

    private function callBeforeFirstPublicationCallback(): void
    {
        if ($this->beforeFirstPublicationCallback) {
            $callback = $this->beforeFirstPublicationCallback;
            $callback($this);
        }
    }

    public function publish(DomainEventStreamInterface $domainMessages)
    {
        if ($this->first) {
            $this->first = false;
            $this->callBeforeFirstPublicationCallback();
        }

        parent::publish($domainMessages);
    }
}
