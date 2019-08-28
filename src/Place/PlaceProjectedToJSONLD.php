<?php
declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Place;

use Broadway\Serializer\SerializableInterface;

final class PlaceProjectedToJSONLD implements SerializableInterface
{
    /**
     * @var string
     */
    private $placeId;

    /**
     * @var string
     */
    private $iri;

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

    /**
     * @return array
     */
    public function serialize()
    {
        return [
            'item_id' => $this->placeId,
            'iri' => $this->iri,
        ];
    }

    /**
     * @param array $data
     * @return static
     */
    public static function deserialize(array $data)
    {
        return new static($data['item_id'], $data['iri']);
    }
}
