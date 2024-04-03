<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Place;

use Broadway\Serializer\Serializable;

final class PlaceProjectedToJSONLD implements Serializable
{
    private string $placeId;

    private string $iri;

    public function __construct(string $placeId, string $iri)
    {
        $this->placeId = $placeId;
        $this->iri = $iri;
    }

    public function getItemId(): string
    {
        return $this->placeId;
    }

    public function getIri(): string
    {
        return $this->iri;
    }

    public function serialize(): array
    {
        return [
            'item_id' => $this->placeId,
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
