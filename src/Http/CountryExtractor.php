<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Country;
use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;

final class CountryExtractor
{
    public function getCountryFromQuery(
        ParameterBagInterface $parameterBag,
        ?Country $defaultCountry
    ): ?Country {
        return $parameterBag->getStringFromParameter(
            'addressCountry',
            null !== $defaultCountry ? $defaultCountry->toString() : null,
            function (string $country) {
                try {
                    return new Country(strtoupper($country));
                } catch (UnsupportedParameterValue $e) {
                    throw new UnsupportedParameterValue("Unknown country code '{$country}'.");
                }
            }
        );
    }
}
