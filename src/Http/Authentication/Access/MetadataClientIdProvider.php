<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use CultuurNet\UDB3\Search\Http\Authentication\MetadataGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenProvider;
use CultuurNet\UDB3\Search\LoggerAwareTrait;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\NullLogger;

final class MetadataClientIdProvider implements ClientIdProvider
{
    use LoggerAwareTrait;
    private ManagementTokenProvider $managementTokenProvider;

    private MetadataGenerator $metadataGenerator;

    public function __construct(
        ManagementTokenProvider $managementTokenProvider,
        MetadataGenerator $metadataGenerator
    ) {
        $this->managementTokenProvider = $managementTokenProvider;
        $this->metadataGenerator = $metadataGenerator;
        $this->setLogger(new NullLogger());
    }

    public function hasSapiAccess(string $clientId): bool
    {
        $oAuthServerDown = false;
        $metadata = [];

        try {
            $metadata = $this->metadataGenerator->get(
                $clientId,
                $this->managementTokenProvider->token()
            );

            if ($metadata === null) {
                throw new InvalidClient();
            }
        } catch (ConnectException $connectException) {
            $this->logger->error('OAuth server was detected as down, this results in disabling authentication');
            $oAuthServerDown = true;
        }

        if (!$oAuthServerDown && !$this->hasAccess($metadata)) {
            return false;
        }

        return true;
    }

    private function hasAccess(array $metadata): bool
    {
        if (empty($metadata)) {
            return false;
        }

        if (empty($metadata['publiq-apis'])) {
            return false;
        }

        $apis = explode(' ', $metadata['publiq-apis']);
        return in_array('sapi', $apis, true);
    }
}
