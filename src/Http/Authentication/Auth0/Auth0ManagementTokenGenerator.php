<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Auth0;

use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementToken;
use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementTokenGenerator;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class Auth0ManagementTokenGenerator implements ManagementTokenGenerator, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Client $client;

    private string $domain;

    private string $clientId;

    private string $clientSecret;

    private string $audience;

    public function __construct(
        Client $client,
        string $domain,
        string $clientId,
        string $clientSecret,
        string $audience
    ) {
        $this->domain = $domain;
        $this->client = $client;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->audience = $audience;
        $this->logger = new NullLogger();
    }

    public function newToken(): ManagementToken
    {
        $response = $this->client->post(
            'https://' . $this->domain . '/oauth/token',
            [
                'headers' => ['content-type' => 'application/json'],
                'json' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'audience' => 'https://' . $this->audience,
                    'grant_type' => 'client_credentials',
                ],
            ]
        );

        if ($response->getStatusCode() !== 200) {
            $message = 'Auth0 error when getting token: ' . $response->getStatusCode();

            if ($response->getStatusCode() >= 500) {
                $this->logger->error($message);
                throw new ConnectException(
                    $message,
                    new Request('POST', 'https://' . $this->domain . '/oauth/token')
                );
            }

            $this->logger->info($message);
        }

        $res = Json::decodeAssociatively($response->getBody()->getContents());
        return new ManagementToken(
            $res['access_token'],
            new DateTimeImmutable(),
            $res['expires_in']
        );
    }
}
