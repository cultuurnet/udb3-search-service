<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class Auth0TokenProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_a_stored_token(): void
    {
        $auth0Token = new Auth0Token(
            'my_auth0_token',
            new DateTimeImmutable('2021-06-21T08:40:00+0000'),
            86400
        );

        $auth0TokenRepository = $this->createMock(Auth0TokenRepository::class);
        $auth0TokenRepository->expects($this->once())
            ->method('get')
            ->willReturn($auth0Token);

        $auth0TokenProvider = new Auth0TokenProvider(
            $auth0TokenRepository,
            new Auth0Client(
                $this->createMock(Client::class),
                'domain',
                'clientId',
                'clientSecret'
            )
        );

        $this->assertEquals($auth0Token, $auth0TokenProvider->get());
    }

    /**
     * @test
     */
    public function it_stores_a_new_token_when_repo_is_empty(): void
    {
        $auth0TokenRepository = $this->createMock(Auth0TokenRepository::class);
        $auth0TokenRepository->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'my_auth0_token',
                'expires_in' => 86400,
            ])),
        ]);

        $auth0TokenProvider = new Auth0TokenProvider(
            $auth0TokenRepository,
            new Auth0Client(
                new Client(['handler' => $mockHandler]),
                'domain',
                'clientId',
                'clientSecret'
            )
        );

        $actualAuth0Token = $auth0TokenProvider->get();
        $this->assertEquals('my_auth0_token', $actualAuth0Token->getToken());
        $this->assertEquals(86400, $actualAuth0Token->getExpiresIn());
    }

    /**
     * @test
     */
    public function it_stores_a_new_token_when_token_is_expired(): void
    {
        $auth0Token = new Auth0Token(
            'my_expired_auth0_token',
            new DateTimeImmutable(),
            60
        );

        $auth0TokenRepository = $this->createMock(Auth0TokenRepository::class);
        $auth0TokenRepository->expects($this->once())
            ->method('get')
            ->willReturn($auth0Token);

        $mockHandler = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'my_new_auth0_token',
                'expires_in' => 86400,
            ])),
        ]);

        $auth0TokenProvider = new Auth0TokenProvider(
            $auth0TokenRepository,
            new Auth0Client(
                new Client(['handler' => $mockHandler]),
                'domain',
                'clientId',
                'clientSecret'
            )
        );

        $actualAuth0Token = $auth0TokenProvider->get();
        $this->assertEquals('my_new_auth0_token', $actualAuth0Token->getToken());
        $this->assertEquals(86400, $actualAuth0Token->getExpiresIn());
    }
}
