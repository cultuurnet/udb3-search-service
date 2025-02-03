<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Access;

use CultuurNet\UDB3\Search\Http\Authentication\Keycloak\KeycloakMetadataGenerator;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenProvider;
use CultuurNet\UDB3\Search\Http\Authentication\Token\ManagementTokenRepository;
use CultuurNet\UDB3\Search\Http\Authentication\Token\Token;
use CultuurNet\UDB3\Search\Http\Authentication\Token\TokenGenerator;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class MetadataClientIdProviderTest extends TestCase
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
    public function it_allows_sapi_access_when_oauth_server_is_down(): void
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://search.uitdatabank.be')
            ->withHeader('x-client-id', 'my_active_client_id');
        $mockHandler = new MockHandler([new ConnectException('No connection with OAuth server', $request)]);

        $metaDataClientIdProvider = new MetadataClientIdProvider(
            $this->managementTokenProvider,
            new KeycloakMetadataGenerator(
                new Client(['handler' => $mockHandler]),
                'domain',
                'realm'
            )
        );

        $this->assertTrue($metaDataClientIdProvider->hasSapiAccess('my_active_client_id'));
    }
}
