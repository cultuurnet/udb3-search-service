<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Token;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ManagementTokenProviderTest extends TestCase
{
    /**
     * @test
     */
    public function it_generates_new_token_if_no_token_in_repository(): void
    {
        $tokenRepository = $this->createMock(ManagementTokenRepository::class);
        $tokenRepository->expects($this->atLeast(1))
            ->method('get')
            ->willReturn(null);

        $token = new Token(
            'my_token',
            new DateTimeImmutable(),
            3600
        );

        $tokenRepository->expects($this->once())
            ->method('set')
            ->with($token);

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenGenerator->expects($this->atLeast(1))
            ->method('managementToken')
            ->willReturn($token);

        $service = new ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository
        );

        $result = $service->token();

        $this->assertEquals('my_token', $result);
    }

    /**
     * @test
     */
    public function it_returns_token_if_valid_token_is_in_repository(): void
    {
        $token = new Token(
            'my_token',
            new DateTimeImmutable(),
            3600
        );

        $tokenRepository = $this->createMock(ManagementTokenRepository::class);
        $tokenRepository->expects($this->atLeast(1))
            ->method('get')
            ->willReturn($token);

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenGenerator->expects($this->never())
            ->method('managementToken');

        $service = new ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository
        );

        $result = $service->token();

        $this->assertEquals('my_token', $result);
    }

    /**
     * @test
     */
    public function it_generates_new_token_if_current_is_about_to_expire(): void
    {
        // A token is also considered as expired when it will expire within 5 minutes.
        $expiredToken = new Token(
            'expired_token',
            new DateTimeImmutable(),
            300
        );

        $newToken = new Token(
            'new_token',
            new DateTimeImmutable(),
            3600
        );

        $tokenRepository = $this->createMock(ManagementTokenRepository::class);
        $tokenRepository->expects($this->atLeast(1))
            ->method('get')
            ->willReturn($expiredToken);

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenGenerator->expects($this->atLeast(1))
            ->method('managementToken')
            ->willReturn($newToken);

        $service = new ManagementTokenProvider(
            $tokenGenerator,
            $tokenRepository
        );

        $result = $service->token();

        $this->assertEquals('new_token', $result);
    }
}
