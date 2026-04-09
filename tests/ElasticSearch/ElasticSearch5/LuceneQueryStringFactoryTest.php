<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\ElasticSearch5;

use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryString;
use CultuurNet\UDB3\Search\ElasticSearch\LuceneQueryStringFactory;
use CultuurNet\UDB3\Search\ElasticSearch5Test;
use PHPUnit\Framework\TestCase;

final class LuceneQueryStringFactoryTest extends TestCase implements ElasticSearch5Test
{
    private LuceneQueryStringFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new LuceneQueryStringFactory();
        $this->factory->enableElasticSearch5CompatibilityMode();
    }

    /**
     * @test
     */
    public function it_does_not_rewrite_type_filter_on_es5(): void
    {
        $actual = $this->factory->fromString('_type:event');
        $expected = new LuceneQueryString('_type:event');
        $this->assertEquals($expected, $actual);
    }
}
