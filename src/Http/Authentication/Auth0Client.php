<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use GuzzleHttp\Client;

final class Auth0Client
{
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
    }

    public function getToken(): string
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

        $res = json_decode($response->getBody()->getContents(), true);
        return $res['access_token'];
    }

    public function getMetadata(string $clientId, string $token): array
    {
        $response = $this->client->get(
            'https://' . $this->domain . '/api/v2/clients/' . $clientId,
            [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]
        );

        $res = json_decode($response->getBody()->getContents(), true);
        return $response->getStatusCode() !== 200 || empty($res['client_metadata']) ? [] : $res['client_metadata'];
    }
}
