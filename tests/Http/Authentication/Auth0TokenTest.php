<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class Auth0TokenTest extends TestCase
{
    /**
     * @var DateTimeImmutable
     */
    private $issuedAt;

    /**
     * @var Auth0Token
     */
    private $auth0Token;

    protected function setUp(): void
    {
        $this->issuedAt = new DateTimeImmutable();

        $this->auth0Token = new Auth0Token(
            'my_auth0_token',
            $this->issuedAt,
            10
        );
    }

    /**
     * @test
     */
    public function it_manages_token_properties(): void
    {
        $this->assertEquals('my_auth0_token', $this->auth0Token->getToken());
        $this->assertEquals($this->issuedAt, $this->auth0Token->getIssuedAt());
        $this->assertEquals(10, $this->auth0Token->getExpiresIn());
    }

    /**
     * @test
     */
    public function it_calculates_expires_at(): void
    {
        $this->assertEquals(
            $this->issuedAt->add(new DateInterval('PT10S')),
            $this->auth0Token->getExpiresAt()
        );
    }
}
