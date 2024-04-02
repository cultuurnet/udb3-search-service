<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

use PHPUnit\Framework\TestCase;

final class PathEndIdUrlParserTest extends TestCase
{
    private PathEndIdUrlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new PathEndIdUrlParser();
    }

    /**
     * @test
     */
    public function it_returns_the_last_part_of_the_path_as_the_id(): void
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }

    /**
     * @test
     */
    public function it_works_with_a_trailing_slash(): void
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4/';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }

    /**
     * @test
     */
    public function it_works_with_trailing_spaces(): void
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4    ';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }

    /**
     * @test
     */
    public function it_works_with_a_trailing_slash_and_spaces(): void
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4/    ';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }
}
