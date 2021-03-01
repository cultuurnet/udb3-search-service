<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Event;

use Broadway\Serializer\SerializableInterface;

final class EventProjectedToJSONLD implements SerializableInterface
{
    /**
     * @var string
     */
    private $eventId;

    /**
     * @var string
     */
    private $iri;

    public function __construct(string $eventId, string $iri)
    {
        $this->eventId = $eventId;
        $this->iri = $iri;
    }

    public function getItemId(): string
    {
        return $this->eventId;
    }

    public function getIri(): string
    {
        return $this->iri;
    }

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'item_id' => $this->eventId,
            'iri' => $this->iri,
        ];
    }

    /**
     * @return static
     */
    public static function deserialize(array $data)
    {
        return new static($data['item_id'], $data['iri']);
    }
}
