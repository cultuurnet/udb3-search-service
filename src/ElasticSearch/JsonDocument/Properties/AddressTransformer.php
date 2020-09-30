<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerLogger;

final class AddressTransformer implements JsonTransformer
{
    /**
     * @var JsonTransformerLogger
     */
    private $logger;

    /**
     * @var bool
     */
    private $addressRequired;

    public function __construct(JsonTransformerLogger $logger, $addressRequired)
    {
        $this->logger = $logger;
        $this->addressRequired = $addressRequired;
    }

    public function transform(array $from, array $draft = []): array
    {
        $mainLanguage = $from['mainLanguage'] ?? 'nl';

        if (!isset($from['address'])) {
            if ($this->addressRequired) {
                $this->logger->logMissingExpectedField('address');
            }
            return $draft;
        }

        if (!isset($from['address'][$mainLanguage])) {
            $this->logger->logMissingExpectedField("address.{$mainLanguage}");
        }

        $addressLanguages = array_keys($from['address']);
        $fields = ['addressCountry', 'addressLocality', 'postalCode', 'streetAddress'];
        $copiedAddresses = [];

        foreach ($addressLanguages as $addressLanguage) {
            $address = $from['address'][$addressLanguage];

            foreach ($fields as $field) {
                if (!isset($address[$field])) {
                    $this->logger->logMissingExpectedField("address.{$addressLanguage}.{$field}");
                    continue;
                }

                $copiedAddresses[$addressLanguage][$field] = $address[$field];
            }
        }

        if (!empty($copiedAddresses)) {
            $draft['address'] = $copiedAddresses;
        }

        return $draft;
    }
}
