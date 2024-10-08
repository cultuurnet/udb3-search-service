<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\AMQP;

use InvalidArgumentException;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Search\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Search\Deserializer\NotWellFormedException;
use CultuurNet\UDB3\Search\Json;

final class DomainMessageJSONDeserializer implements DeserializerInterface
{
    /**
     * Fully qualified class name of the payload. This class should implement
     * Broadway\Serializer\SerializableInterface.
     */
    private string $payloadClass;

    public function __construct(string $payloadClass)
    {
        if (!in_array(Serializable::class, (array) class_implements($payloadClass))) {
            throw new InvalidArgumentException(
                sprintf(
                    'Class \'%s\' does not implement ' . Serializable::class,
                    $payloadClass
                )
            );
        }

        $this->payloadClass = $payloadClass;
    }

    public function deserialize(string $data): DomainMessage
    {
        $data = Json::decodeAssociatively($data);

        if (null === $data) {
            throw new NotWellFormedException('Invalid JSON');
        }

        return new DomainMessage(
            $data['id'],
            (int) $data['playhead'],
            Metadata::deserialize($data['metadata']),
            $this->payloadClass::deserialize($data['payload']),
            DateTime::fromString($data['recorded_on'])
        );
    }
}
