<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\DayOfWeek;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class DayOfWeekOfferRequestParserTest extends TestCase
{
    private DayOfWeekOfferRequestParser $parser;

    /**
     * @var OfferQueryBuilderInterface&MockObject
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->parser = new DayOfWeekOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_adds_a_single_day_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['dayOfWeek' => 'wednesday']);

        $this->queryBuilder->expects($this->once())
            ->method('withDayOfWeekFilter')
            ->with(new DayOfWeek('wednesday'))
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_multiple_days_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['dayOfWeek' => 'friday,saturday,sunday']);

        $this->queryBuilder->expects($this->once())
            ->method('withDayOfWeekFilter')
            ->with(new DayOfWeek('friday'), new DayOfWeek('saturday'), new DayOfWeek('sunday'))
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_accepts_mixed_case_values(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['dayOfWeek' => 'Friday,SATURDAY']);

        $this->queryBuilder->expects($this->once())
            ->method('withDayOfWeekFilter')
            ->with(new DayOfWeek('friday'), new DayOfWeek('saturday'))
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_does_not_add_a_filter_when_the_parameter_is_absent(): void
    {
        $request = ServerRequestFactory::createFromGlobals();

        $this->queryBuilder->expects($this->never())
            ->method('withDayOfWeekFilter');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_for_an_invalid_day_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['dayOfWeek' => 'someday']);

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('Unknown day of week value "someday"');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }
}
