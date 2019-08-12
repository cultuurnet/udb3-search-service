<?php

namespace CultuurNet\UDB3\Search\ElasticSearch;

class PathEndIdUrlParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PathEndIdUrlParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new PathEndIdUrlParser();
    }

    /**
     * @test
     */
    public function it_returns_the_last_part_of_the_path_as_the_id()
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }

    /**
     * @test
     */
    public function it_works_with_a_trailing_slash()
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4/';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }

    /**
     * @test
     */
    public function it_works_with_trailing_spaces()
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4    ';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }

    /**
     * @test
     */
    public function it_works_with_a_trailing_slash_and_spaces()
    {
        $url = 'http://foo.bar/event/ab314bf2-703d-4411-ba0d-d2a0c056a7b4/    ';
        $expectedId = 'ab314bf2-703d-4411-ba0d-d2a0c056a7b4';
        $actualId = $this->parser->getIdFromUrl($url);
        $this->assertEquals($expectedId, $actualId);
    }
}
