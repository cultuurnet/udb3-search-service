<?php
declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\Serializer\SerializableInterface;

final class OrganizerProjectedToJSONLD implements SerializableInterface
{
    /**
     * @var string
     */
    private $id;

    public function __construct(string $id, string $iri)
    {
        $this->id = (string) $id;
        $this->iri = (string) $iri;
    }

    public function getId(): string
    {
        return $this->id;
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
            'id' => $this->getId(),
            'iri' => $this->getIri(),
        ];
    }

    /**
     * @param array $data
     * @return OrganizerProjectedToJSONLD
     */
    public static function deserialize(array $data)
    {
        return new self($data['id'], $data['iri']);
    }
}
