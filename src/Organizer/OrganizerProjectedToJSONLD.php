<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Organizer;

use Broadway\Serializer\Serializable;

final class OrganizerProjectedToJSONLD implements Serializable
{
    private string $id;

    private string $iri;

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

    public function serialize(): array
    {
        return [
            'id' => $this->getId(),
            'iri' => $this->getIri(),
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self($data['id'], $data['iri']);
    }
}
