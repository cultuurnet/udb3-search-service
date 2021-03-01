<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Parameters;

abstract class AbstractSupportedParameters
{
    /**
     * @return string[] The list of parameters on the white list.
     */
    abstract protected function getSupportedParameters(): array;

    /**
     * @return string[]
     */
    protected function getGloballySupportedParameters(): array
    {
        return [
            'apiKey',
            'embed',
            'start',
            'limit',
            'XDEBUG_SESSION_START',
        ];
    }

    /**
     * @param string[] $parameters
     * @throws \InvalidArgumentException
     */
    public function guardAgainstUnsupportedParameters(array $parameters): void
    {
        $whiteList = array_merge($this->getGloballySupportedParameters(), $this->getSupportedParameters());
        $unknownParameters = array_diff($parameters, $whiteList);
        if (count($unknownParameters) > 0) {
            throw new \InvalidArgumentException(
                'Unknown query parameter(s): ' . implode(', ', $unknownParameters)
            );
        }
    }
}
