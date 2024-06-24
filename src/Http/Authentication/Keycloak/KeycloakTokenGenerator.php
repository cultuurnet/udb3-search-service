<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Keycloak;

use CultuurNet\UDB3\Search\Http\Authentication\Token\Token;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

final class KeycloakTokenGenerator implements TokenGenerator
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

    public function managementToken(): Token
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

        return new Token(
            $json['access_token'],
            new DateTimeImmutable(),
            $json['expires_in']
        );
    }

    public function loginToken(): Token
    {
        $request = new Request(
            'POST',
            $this->domain . '/oauth/token',
            [
                'Content-Type' => 'application/json',
            ],
            Json::encode([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
                'audience' => 'https://api.publiq.be',
            ])
        );

        $response = $this->client->sendRequest($request);

        $json = Json::decodeAssociatively($response->getBody()->getContents());

        return new Token(
            $json['access_token'],
            new DateTimeImmutable(),
            $json['expires_in']
        );
    }
}
