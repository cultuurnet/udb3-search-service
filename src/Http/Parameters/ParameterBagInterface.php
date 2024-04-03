<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

use DateTimeImmutable;

interface ParameterBagInterface
{
    public function getArrayFromParameter(
        string $queryParameter,
        callable $callback = null
    ): array;

    /**
     * @return mixed|null
     */
    public function getStringFromParameter(
        string $parameterName,
        ?string $defaultValue = null,
        callable $callback = null
    );

    /**
     * @return mixed|null
     */
    public function getIntegerFromParameter(
        string $parameterName,
        string $defaultValue = null,
        callable $callback = null
    );

    public function getExplodedStringFromParameter(
        string $parameterName,
        ?string $defaultValueAsString = null,
        callable $callback = null,
        string $delimiter = ','
    ): array;

    public function getBooleanFromParameter(
        string $parameterName,
        ?string $defaultValueAsString = null
    ): ?bool;

    public function getDateTimeFromParameter(
        string $queryParameter,
        ?string $defaultValueAsString = null
    ): ?DateTimeImmutable;
}
