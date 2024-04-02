<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

use DateTimeImmutable;

interface ParameterBagInterface
{
    /**
     * @param string $queryParameter
     * @return array
     */
    public function getArrayFromParameter(
        $queryParameter,
        callable $callback = null
    );

    /**
     * @param string $parameterName
     * @param string|null $defaultValue
     * @param callable $callback
     * @return mixed|null
     */
    public function getStringFromParameter(
        $parameterName,
        $defaultValue = null,
        callable $callback = null
    );

    /**
     * @param string $parameterName
     * @param string|null $defaultValue
     * @param callable $callback
     * @return mixed|null
     */
    public function getIntegerFromParameter(
        $parameterName,
        $defaultValue = null,
        callable $callback = null
    );

    /**
     * @param string $parameterName
     * @param string|null $defaultValueAsString
     * @param string $delimiter
     * @return array
     */
    public function getExplodedStringFromParameter(
        $parameterName,
        $defaultValueAsString = null,
        callable $callback = null,
        $delimiter = ','
    );

    /**
     * @param string $parameterName
     * @param string|null $defaultValueAsString
     * @return bool|null
     */
    public function getBooleanFromParameter(
        $parameterName,
        $defaultValueAsString = null
    );

    /**
     * @param string $queryParameter
     * @param string|null $defaultValueAsString
     * @return DateTimeImmutable|null
     */
    public function getDateTimeFromParameter(
        $queryParameter,
        $defaultValueAsString = null
    );
}
