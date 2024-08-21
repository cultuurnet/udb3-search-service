<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

use DateTimeImmutable;
use DateTime;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class ArrayParameterBagAdapter implements ParameterBagInterface
{
    private array $parameterBag;

    private string $resetValue;

    public function __construct(array $parameterBag, string $resetValue = '*')
    {
        $this->parameterBag = $parameterBag;
        $this->resetValue = $resetValue;
    }

    public function getArrayFromParameter(string $queryParameter, callable $callback = null): array
    {
        if (empty($this->get($queryParameter))) {
            return [];
        }

        $callback = $this->ensureCallback($callback);
        $values = (array) $this->get($queryParameter);

        return array_map($callback, $values);
    }

    /**
     * @todo Remove docblock when upgrading to PHP8
     * @param string|bool|null $defaultValue
     * @return mixed|null
     */
    public function getStringFromParameter(
        string $parameterName,
        $defaultValue = null,
        callable $callback = null
    ) {
        $parameterValue = $this->get($parameterName);
        $callback = $this->ensureCallback($callback);

        if (is_array($parameterValue)) {
            throw new UnsupportedParameterValue(
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
     * @return mixed|null
     */
    public function getIntegerFromParameter(
        string $parameterName,
        string $defaultValue = null,
        callable $callback = null
    ) {
        $callback = $this->ensureCallback($callback);

        $intCallback = static function ($value) use ($callback) {
            $int = (int) $value;
            return $callback($int);
        };

        return $this->getStringFromParameter($parameterName, $defaultValue, $intCallback);
    }

    public function getExplodedStringFromParameter(
        string $parameterName,
        ?string $defaultValueAsString = null,
        callable $callback = null,
        string $delimiter = ','
    ): array {
        $callback = $this->ensureCallback($callback);

        $asString = $this->getStringFromParameter(
            $parameterName,
            $defaultValueAsString
        );

        if ($asString === null) {
            return [];
        }

        /** @var string[] $asArray */
        $asArray = explode($delimiter, $asString);

        return array_map($callback, $asArray);
    }

    /**
     * @todo Remove docblock when upgrading to PHP8
     * @param string|bool|null $defaultValueAsString
     */
    public function getBooleanFromParameter(
        string $parameterName,
        $defaultValueAsString = null
    ): ?bool {
        $callback = static function ($mixed) {
            if ($mixed === null || $mixed === '') {
                return null;
            }

            return filter_var($mixed, FILTER_VALIDATE_BOOLEAN);
        };

        return $this->getStringFromParameter($parameterName, $defaultValueAsString, $callback);
    }

    public function getDateTimeFromParameter(string $queryParameter, ?string $defaultValueAsString = null): ?DateTimeImmutable
    {
        $callback = static function ($asString) use ($queryParameter) {
            // When you use a + in a URL it gets interpreted as a space. This can be resolved by using %2B instead, or
            // something like urlencode() when programming an actual integration, but it's convenient to interpret the
            // spaces in dates as plus signs for testing purposes. The date format we expect should have no spaces
            // anyway, so if we find a space it's more likely that it was meant to be a +.
            $asString = str_replace(' ', '+', (string)$asString);

            $asDateTime = DateTimeImmutable::createFromFormat(DateTime::ATOM, $asString);

            if (!$asDateTime) {
                throw new UnsupportedParameterValue(
                    "{$queryParameter} should be an ISO-8601 datetime, for example 2017-04-26T12:20:05+01:00"
                );
            }

            return $asDateTime;
        };

        return $this->getStringFromParameter($queryParameter, $defaultValueAsString, $callback);
    }

    /**
     * @return mixed|null
     */
    private function get(string $queryParameter)
    {
        if (!isset($this->parameterBag[$queryParameter])) {
            return null;
        }

        return $this->parameterBag[$queryParameter];
    }

    private function areDefaultFiltersEnabled(): bool
    {
        // Don't pass a default value here as it will cause an infinite loop.
        $disabled = $this->getBooleanFromParameter('disableDefaultFilters');

        // Instead check if the returned value is null, and if so always set it
        // to false as it means the disableDefaultFilters parameter is not set.
        $disabled ??= false;

        return !$disabled;
    }

    private function ensureCallback(callable $callback = null): callable
    {
        if ($callback !== null) {
            return $callback;
        }

        $passThroughCallback = static fn ($value) => $value;

        return $passThroughCallback;
    }
}
