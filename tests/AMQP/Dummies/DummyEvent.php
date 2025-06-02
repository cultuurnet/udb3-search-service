<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP\Dummies;

use Broadway\Serializer\Serializable;

final class DummyEvent implements Serializable
{
    private string $id;

    private string $content;

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
