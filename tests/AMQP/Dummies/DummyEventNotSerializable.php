<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP\Dummies;

final class DummyEventNotSerializable
{
    private string $id;
    private string $content;

    public function __construct(string $id, string $content)
    {
        $this->id = $id;
        $this->content = $content;
    }

    // These getters are never used, just here so phpstan is happy

    public function getId(): string
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
