<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

use InvalidArgumentException;

final class ArrayParameterBagAdapter implements ParameterBagInterface
{
    /**
     * @var array
     */
    private $parameterBag;

    /**
     * @var string
     */
    private $resetValue;

    /**
     * @param string $resetValue
     */
    public function __construct(array $parameterBag, $resetValue = '*')
    {
        $this->parameterBag = $parameterBag;
        $this->resetValue = $resetValue;
    }

    /**
     * @param string $queryParameter
     * @return array
     */
    public function getArrayFromParameter($queryParameter, callable $callback = null)
    {
        if (empty($this->get($queryParameter))) {
            return [];
        }

        $callback = $this->ensureCallback($callback);
        $values = (array) $this->get($queryParameter);

        return array_map($callback, $values);
    }

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
    ) {
        $parameterValue = $this->get($parameterName);
        $callback = $this->ensureCallback($callback);

        if (is_array($parameterValue)) {
            throw new InvalidArgumentException(
                "The parameter \"{$parameterName}\" can only have a single value."
            );
        }

        if ($parameterValue === $this->resetValue) {
            return null;
        }

        if ($parameterValue === null && $defaultValue !== null && $this->areDefaultFiltersEnabled()) {
            $parameterValue = $defaultValue;
        }

        if ($parameterValue === null) {
            return null;
        }

        return $callback($parameterValue);
    }

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
    ) {
        $callback = $this->ensureCallback($callback);

        $intCallback = static function ($value) use ($callback) {
            $int = (int) $value;
            return $callback($int);
        };

        return $this->getStringFromParameter($parameterName, $defaultValue, $intCallback);
    }

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
    ) {
        $callback = $this->ensureCallback($callback);

        $asString = $this->getStringFromParameter(
            $parameterName,
            $defaultValueAsString
        );

        if ($asString === null) {
            return [];
        }

        $asArray = explode($delimiter, $asString);

        return array_map($callback, $asArray);
    }

    /**
     * @param string $parameterName
     * @param string|null $defaultValueAsString
     * @return bool|null
     */
    public function getBooleanFromParameter(
        $parameterName,
        $defaultValueAsString = null
    ) {
        $callback = static function ($mixed) {
            if ($mixed === null || $mixed === '') {
                return null;
            }

            return filter_var($mixed, FILTER_VALIDATE_BOOLEAN);
        };

        return $this->getStringFromParameter($parameterName, $defaultValueAsString, $callback);
    }

    /**
     * @param string $queryParameter
     * @param string|null $defaultValueAsString
     * @return \DateTimeImmutable|null
     */
    public function getDateTimeFromParameter($queryParameter, $defaultValueAsString = null)
    {
        $callback = static function ($asString) use ($queryParameter) {
            // When you use a + in a URL it gets interpreted as a space. This can be resolved by using %2B instead, or
            // something like urlencode() when programming an actual integration, but it's convenient to interpret the
            // spaces in dates as plus signs for testing purposes. The date format we expect should have no spaces
            // anyway, so if we find a space it's more likely that it was meant to be a +.
            $asString = str_replace(' ', '+', $asString);

            $asDateTime = \DateTimeImmutable::createFromFormat(\DateTime::ATOM, $asString);

            if (!$asDateTime) {
                throw new InvalidArgumentException(
                    "{$queryParameter} should be an ISO-8601 datetime, for example 2017-04-26T12:20:05+01:00"
                );
            }

            return $asDateTime;
        };

        return $this->getStringFromParameter($queryParameter, $defaultValueAsString, $callback);
    }

    private function get(string $queryParameter, $default = null)
    {
        if (!isset($this->parameterBag[$queryParameter])) {
            return $default;
        }

        return $this->parameterBag[$queryParameter];
    }

    private function areDefaultFiltersEnabled(): bool
    {
        // Don't pass a default value here as it will cause an infinite loop.
        $disabled = $this->getBooleanFromParameter('disableDefaultFilters');

        // Instead check if the returned value is null, and if so always set it
        // to false as it means the disableDefaultFilters parameter is not set.
        $disabled = $disabled === null ? false : $disabled;

        return !$disabled;
    }

    private function ensureCallback(callable $callback = null): callable
    {
        if ($callback !== null) {
            return $callback;
        }

        $passThroughCallback = static function ($value) {
            return $value;
        };

        return $passThroughCallback;
    }
}
