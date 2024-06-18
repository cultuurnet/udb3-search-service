<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Keycloak;

use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementToken;
use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementTokenGenerator;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

final class KeycloakManagementTokenGenerator implements ManagementTokenGenerator
{
    private ClientInterface $client;
    private string $domain;
    private string $clientId;
    private string $clientSecret;
    private string $audience;

    public function __construct(
        ClientInterface $client,
        string $domain,
        string $clientId,
        string $clientSecret,
        string $audience
    ) {
        $this->client = $client;
        $this->domain = $domain;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->audience = $audience;
    }

    public function newToken(): ManagementToken
    {
        $request = new Request(
            'POST',
            $this->domain . '/realms/master/protocol/openid-connect/token',
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            http_build_query([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'audience' => 'https://' . $this->audience,
                'grant_type' => 'client_credentials',
            ])
        );

        $response = $this->client->sendRequest($request);

        $json = Json::decodeAssociatively($response->getBody()->getContents());

        return new ManagementToken(
            $json['access_token'],
            new DateTimeImmutable(),
            $json['expires_in']
        );
    }
}
