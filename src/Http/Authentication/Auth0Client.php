<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultuurNet\UDB3\Search\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class Auth0Client implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Client $client;

    private string $domain;

    public function __construct(
        Client $client,
        string $domain
    ) {
        $this->domain = $domain;
        $this->client = $client;
        $this->logger = new NullLogger();
    }

    public function getMetadata(string $clientId, string $token): ?array
    {
        $response = $this->client->get(
            'https://' . $this->domain . '/api/v2/clients/' . $clientId,
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]
        );

        if ($response->getStatusCode() !== 200) {
            $message = 'Auth0 error when getting metadata: ' . $response->getStatusCode();

            if ($response->getStatusCode() >= 500) {
                $this->logger->error($message);
                throw new ConnectException(
                    $message,
                    new Request('GET', 'https://' . $this->domain . '/api/v2/clients/' . $clientId)
                );
            }

            $this->logger->info($message);
            return null;
        }

        $res = Json::decodeAssociatively($response->getBody()->getContents());
        return $res['client_metadata'] ?? [];
    }
}
