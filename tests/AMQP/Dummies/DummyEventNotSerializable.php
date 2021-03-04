<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP\Dummies;

final class DummyEventNotSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $content;

    /**
     * @param string $id
     * @param string $content
     */
    public function __construct($id, $content)
    {
        $this->id = $id;
        $this->content = $content;
    }
}
