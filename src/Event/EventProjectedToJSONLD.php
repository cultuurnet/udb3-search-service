<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Event;

use Broadway\Serializer\Serializable;

final class EventProjectedToJSONLD implements Serializable
{
    private string $eventId;

    private string $iri;

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

    public function serialize(): array
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
