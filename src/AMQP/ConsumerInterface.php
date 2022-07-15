<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

interface ConsumerInterface
{
    public function isConsuming(): bool;
    public function wait(): void;
}
