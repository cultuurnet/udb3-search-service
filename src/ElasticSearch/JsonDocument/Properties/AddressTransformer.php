<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Logging\CopyJsonLoggerInterface;

class AddressTransformer implements CopyJsonInterface
{
    /**
     * @var CopyJsonLoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $addressRequired;

    public function __construct(CopyJsonLoggerInterface $logger, $addressRequired)
    {
        $this->logger = $logger;
        $this->addressRequired = $addressRequired;
    }

    /**
     * @inheritdoc
     */
    public function copy(\stdClass $from, \stdClass $to)
    {
        $mainLanguage = $from->mainLanguage ?? 'nl';

        if (!isset($from->address)) {
            if ($this->addressRequired) {
                $this->logger->logMissingExpectedField('address');
            }
            return;
        }

        if (!isset($from->address->{$mainLanguage})) {
            $this->logger->logMissingExpectedField("address.{$mainLanguage}");
        }

        $addressLanguages = array_keys(get_object_vars($from->address));
        $fields = ['addressCountry', 'addressLocality', 'postalCode', 'streetAddress'];
        $copiedAddresses = [];

        foreach ($addressLanguages as $addressLanguage) {
            $address = $from->address->{$addressLanguage};
            $copiedAddress = [];

            foreach ($fields as $field) {
                if (!isset($address->{$field})) {
                    $this->logger->logMissingExpectedField("address.{$addressLanguage}.{$field}");
                    continue;
                }

                $copiedAddress[$field] = $address->{$field};
            }

            if (!empty($copiedAddress)) {
                $copiedAddresses[$addressLanguage] = (object) $copiedAddress;
            }
        }

        if (!empty($copiedAddresses)) {
            $to->address = (object) $copiedAddresses;
        }
    }
}
