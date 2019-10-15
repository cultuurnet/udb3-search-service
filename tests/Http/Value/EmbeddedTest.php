<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Value;

use PHPUnit\Framework\TestCase;

class EmbeddedTest extends TestCase
{
    /**
     * @dataProvider provider
     * @param $value
     */
    public function test_it_can_be_initialized_to_false($value)
    {
        $embedded = Embedded::create($value);
        $this->assertEquals($embedded->isTrue(), false);
        $this->assertEquals($embedded->isFalse(), true);
    }

    /**
     * @dataProvider providerForTrue
     * @param $value
     */
    public function test_it_can_be_initialized_to_true($value)
    {
        $embedded = Embedded::create($value);
        $this->assertEquals($embedded->isTrue(), true);
        $this->assertEquals($embedded->isFalse(), false);
    }

    public function provider()
    {
        return array(
            [''],
            [null],
            ['some-string'],
        );
    }

    public function providerForTrue()
    {
        return array(
            ['true'],
            ['1'],
        );
    }
}
