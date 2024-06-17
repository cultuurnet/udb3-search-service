<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use CultuurNet\UDB3\Search\Http\Authentication\ManagementToken\ManagementToken;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class Auth0TokenFileRepositoryTest extends TestCase
{
    private const CACHE_FILE = __DIR__ . '/auth0-token-cache.json';


    private Auth0TokenFileRepository $auth0TokenFileRepository;

    protected function setUp(): void
    {
        $this->auth0TokenFileRepository = new Auth0TokenFileRepository(self::CACHE_FILE);
    }

    protected function tearDown(): void
    {
        if (file_exists(self::CACHE_FILE)) {
            unlink(self::CACHE_FILE);
        }
    }

    /**
     * @test
     */
    public function it_returns_null_when_file_does_not_exist(): void
    {
        $this->assertNull($this->auth0TokenFileRepository->get());
    }

    /**
     * @test
     */
    public function it_stores_an_auth0_token(): void
    {
        $auth0Token = new ManagementToken(
            'my_auth0_token',
            new DateTimeImmutable('2021-06-21T08:40:00+0000'),
            10
        );

        $this->auth0TokenFileRepository->set($auth0Token);

        $this->assertEquals($auth0Token, $this->auth0TokenFileRepository->get());
    }
}
