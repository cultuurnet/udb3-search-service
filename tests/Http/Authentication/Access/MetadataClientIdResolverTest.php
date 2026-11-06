<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use CultuurNet\UDB3\Search\Http\Authentication\Keycloak\KeycloakMetadataGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenProvider;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenRepository;
use CultuurNet\UDB3\Search\Http\Authentication\Token\Token;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
use CultuurNet\UDB3\Search\Json;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class MetadataClientIdResolverTest extends TestCase
{
    private ManagementTokenProvider $managementTokenProvider;

    protected function setUp(): void
    {
        $managementToken = new Token(
            'my_oauth_token',
            new DateTimeImmutable(),
            86400
        );

        /** @var TokenGenerator&MockObject $managementTokenGenerator */
        $managementTokenGenerator = $this->createMock(TokenGenerator::class);
        $managementTokenGenerator
            ->method('managementToken')
            ->willReturn($managementToken);

        /** @var ManagementTokenRepository&MockObject $managementTokenRepository */
        $managementTokenRepository = $this->createMock(ManagementTokenRepository::class);
        $managementTokenRepository
            ->method('get')
            ->willReturn($managementToken);

        $this->managementTokenProvider = new ManagementTokenProvider(
            $managementTokenGenerator,
            $managementTokenRepository
        );
    }

    /**
     * @test
     */
    public function it_allows_sapi_access_when_permission_present(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], Json::encode([
                0 => [
                    'defaultClientScopes' => [
                        'publiq-api-ups-scope',
                        'publiq-api-entry-scope',
                        'publiq-api-sapi-scope',
                    ],
                ],
            ])),
        ]);

        $metadataClientIdResolver = new MetadataClientIdResolver(
            $this->managementTokenProvider,
            new KeycloakMetadataGenerator(
                new Client(['handler' => $mockHandler]),
                'domain',
                'realm'
            )
        );

        $this->assertTrue($metadataClientIdResolver->hasSapiAccess('my_active_client_id'));
    }

    /**
     * @test
     */
    public function it_allows_sapi_access_when_oauth_server_is_down(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be')
            ->withHeader('x-client-id', 'my_active_client_id');
        $mockHandler = new MockHandler([new ConnectException('No connection with OAuth server', $request)]);

        $metadataClientIdResolver = new MetadataClientIdResolver(
            $this->managementTokenProvider,
            new KeycloakMetadataGenerator(
                new Client(['handler' => $mockHandler]),
                'domain',
                'realm'
            )
        );

        $this->assertTrue($metadataClientIdResolver->hasSapiAccess('my_active_client_id'));
    }

    /**
     * @test
     */
    public function it_does_not_allow_sapi_access_when_metadata_is_missing(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], Json::encode([])),
        ]);

        $metadataClientIdResolver = new MetadataClientIdResolver(
            $this->managementTokenProvider,
            new KeycloakMetadataGenerator(
                new Client(['handler' => $mockHandler]),
                'domain',
                'realm'
            )
        );

        $this->assertFalse($metadataClientIdResolver->hasSapiAccess('my_active_client_id'));
    }

    /**
     * @test
     */
    public function it_does_not_allow_sapi_access_when_permission_is_missing_in_metadata(): void
    {
        $mockHandler = new MockHandler([
            new Response(200, [], Json::encode([
                0 => [
                    'defaultClientScopes' => [
                        'publiq-api-ups-scope',
                        'publiq-api-entry-scope',
                    ],
                ],
            ])),
        ]);

        $metadataClientIdResolver = new MetadataClientIdResolver(
            $this->managementTokenProvider,
            new KeycloakMetadataGenerator(
                new Client(['handler' => $mockHandler]),
                'domain',
                'realm'
            )
        );

        $this->assertFalse($metadataClientIdResolver->hasSapiAccess('my_active_client_id'));
    }
}
