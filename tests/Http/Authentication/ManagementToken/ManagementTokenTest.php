<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\ManagementToken;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ManagementTokenTest extends TestCase
{
    private DateTimeImmutable $issuedAt;

    private ManagementToken $managementToken;

    protected function setUp(): void
    {
        $this->issuedAt = new DateTimeImmutable();

        $this->managementToken = new ManagementToken(
            'my_management_token',
            $this->issuedAt,
            10
        );
    }

    /**
     * @test
     */
    public function it_manages_token_properties(): void
    {
        $this->assertEquals('my_management_token', $this->managementToken->getToken());
        $this->assertEquals($this->issuedAt, $this->managementToken->getIssuedAt());
        $this->assertEquals(10, $this->managementToken->getExpiresIn());
    }

    /**
     * @test
     */
    public function it_calculates_expires_at(): void
    {
        $this->assertEquals(
            $this->issuedAt->add(new DateInterval('PT10S')),
            $this->managementToken->getExpiresAt()
        );
    }
}
