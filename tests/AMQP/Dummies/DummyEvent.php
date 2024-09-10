<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP\Dummies;

use Broadway\Serializer\Serializable;

final class DummyEvent implements Serializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $content;

    public function __construct(string $id, string $content)
    {
        $this->id = $id;
        $this->content = $content;
    }

    /**
     * @return mixed The object instance
     */
    public static function deserialize(array $data)
    {
        return new self(
            $data['id'],
            $data['content']
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
        ];
    }
}
