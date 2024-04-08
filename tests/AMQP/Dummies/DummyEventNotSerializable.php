<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP\Dummies;

final class DummyEventNotSerializable
{
    public string $id;
    public string $content;

    public function __construct(string $id, string $content)
    {
        $this->id = $id;
        $this->content = $content;
    }
}
