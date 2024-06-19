<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Token;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ManagementTokenFileRepositoryTest extends TestCase
{
    private const CACHE_FILE = __DIR__ . '/management-token-cache.json';

    private ManagementTokenFileRepository $managementTokenFileRepository;

    protected function setUp(): void
    {
        $this->managementTokenFileRepository = new ManagementTokenFileRepository(self::CACHE_FILE);
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
        $this->assertNull($this->managementTokenFileRepository->get());
    }

    /**
     * @test
     */
    public function it_stores_a_management_token(): void
    {
        $managementToken = new Token(
            'my_management_token',
            new DateTimeImmutable('2021-06-21T08:40:00+0000'),
            10
        );

        $this->managementTokenFileRepository->set($managementToken);

        $this->assertEquals($managementToken, $this->managementTokenFileRepository->get());
    }
}
