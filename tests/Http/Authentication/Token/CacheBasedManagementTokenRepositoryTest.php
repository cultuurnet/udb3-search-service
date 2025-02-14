<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Authentication\Token;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class CacheBasedManagementTokenRepositoryTest extends TestCase
{
    private CacheBasedManagementTokenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new CacheBasedManagementTokenRepository(new ArrayAdapter());
    }

    /**
     * @test
     */
    public function it_returns_null_when_it_is_not_cached_yet(): void
    {
        $this->assertNull($this->repository->get());
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

        $this->repository->set($managementToken);

        $this->assertEquals($managementToken, $this->repository->get());
    }
}
