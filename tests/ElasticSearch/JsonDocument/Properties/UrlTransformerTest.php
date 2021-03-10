<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UrlTransformerTest extends TestCase
{
    /**
     * @test
     * @dataProvider draftProvider
     */
    public function it_transforms_a_url_to_a_domain(array $from, array $draft): void
    {
        $urlTransformer = new UrlTransformer();

        $this->assertEquals(
            $draft,
            $urlTransformer->transform($from, [])
        );
    }

    public function draftProvider(): array
    {
        return [
            'http://www.publiq.be' => [
                [
                    'url' => 'http://www.publiq.be',
                ],
                [
                    'url' => 'http://www.publiq.be',
                    'domain' => 'publiq.be',
                ],
            ],
            'http://publiq.be' => [
                [
                    'url' => 'http://publiq.be',
                ],
                [
                    'url' => 'http://publiq.be',
                    'domain' => 'publiq.be',
                ],
            ],
            'http://app.publiq.be' => [
                [
                    'url' => 'http://app.publiq.be',
                ],
                [
                    'url' => 'http://app.publiq.be',
                    'domain' => 'app.publiq.be',
                ],
            ],
            'hp://www.publiq.be' => [
                [
                    'url' => 'hp://www.publiq.be',
                ],
                [
                    'url' => 'hp://www.publiq.be',
                    'domain' => 'publiq.be',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidUrlProviders
     */
    public function it_throws_for_invalid_urls(array $from): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new UrlTransformer())->transform($from, []);
    }

    public function invalidUrlProviders(): array
    {
        return [
            'www.publiq.be' => [
                [
                    'url' => 'www.publiq.be',
                ],
            ],
            'publiq.be' => [
                [
                    'url' => 'publiq.be',
                ],
            ],
        ];
    }
}
