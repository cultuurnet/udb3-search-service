<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use DateTimeImmutable;
use GuzzleHttp\Client;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class Auth0Client implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    public function __construct(Client $client, string $domain, string $clientId, string $clientSecret)
    {
        $this->domain = $domain;
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->logger = new NullLogger();
    }

    public function getToken(): ?Auth0Token
    {
        $response = $this->client->post(
            'https://' . $this->domain . '/oauth/token',
            [
                'headers' => ['content-type' => 'application/json'],
                'json' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'audience' => 'https://' . $this->domain . '/api/v2/',
                    'grant_type' => 'client_credentials',
                ],
            ]
        );

        if ($response === null || $response->getStatusCode() !== 200) {
            $this->logger->error(
                'Auth0 error when getting token: ' . ($response ? $response->getStatusCode() : 'unknown')
            );
            return null;
        }

        $res = json_decode($response->getBody()->getContents(), true);
        return new Auth0Token(
            $res['access_token'],
            new DateTimeImmutable(),
            $res['expires_in']
        );
    }

    public function getMetadata(string $clientId, string $token): ?array
    {
        $response = $this->client->get(
            'https://' . $this->domain . '/api/v2/clients/' . $clientId,
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]
        );

        if ($response === null || $response->getStatusCode() !== 200) {
            $this->logger->error(
                'Auth0 error when getting metadata: ' . ($response ? $response->getStatusCode() : 'unknown')
            );
            return null;
        }

        $res = json_decode($response->getBody()->getContents(), true);
        return  $res['client_metadata'];
    }
}
