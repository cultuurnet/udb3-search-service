<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use GuzzleHttp\Client;

final class Auth0TokenGenerator
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

    public function newToken(): string
    {
        $response = $this->client->post(
            'https://' . $this->domain . '/oauth/token',
            [
                'headers' => ['content-type' => 'application/x-www-form-urlencoded'],
                'body' => sprintf(
                    'grant_type=client_credentials&client_id=%s&client_secret=%s&audience=%s',
                    $this->clientId,
                    $this->clientSecret,
                    'https://' . $this->domain . '/api/v2/'
                ),
            ]
        );

        $res = json_decode($response->getBody()->getContents(), true);
        return $res['access_token'];
    }
}
