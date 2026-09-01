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

final class RecurringOnDayOfWeekOfferRequestParserTest extends TestCase
{
    private RecurringOnDayOfWeekOfferRequestParser $parser;

    /**
     * @var OfferQueryBuilderInterface&MockObject
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->parser = new RecurringOnDayOfWeekOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_adds_a_single_day_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['recurringOnDayOfWeek' => 'wednesday']);

        $this->queryBuilder->expects($this->once())
            ->method('withRecurringOnDayOfWeekFilter')
            ->with(DayOfWeek::Wednesday)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_multiple_days_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['recurringOnDayOfWeek' => 'friday,saturday,sunday']);

        $this->queryBuilder->expects($this->once())
            ->method('withRecurringOnDayOfWeekFilter')
            ->with(DayOfWeek::Friday, DayOfWeek::Saturday, DayOfWeek::Sunday)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_rejects_the_array_syntax(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['recurringOnDayOfWeek' => ['friday', 'saturday']]);

        $this->queryBuilder->expects($this->never())
            ->method('withRecurringOnDayOfWeekFilter');

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('The parameter "recurringOnDayOfWeek" can only have a single value.');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_accepts_mixed_case_values(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['recurringOnDayOfWeek' => 'Friday,SATURDAY']);

        $this->queryBuilder->expects($this->once())
            ->method('withRecurringOnDayOfWeekFilter')
            ->with(DayOfWeek::Friday, DayOfWeek::Saturday)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_accepts_a_comma_separated_list_with_spaces(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['recurringOnDayOfWeek' => 'friday, saturday']);

        $this->queryBuilder->expects($this->once())
            ->method('withRecurringOnDayOfWeekFilter')
            ->with(DayOfWeek::Friday, DayOfWeek::Saturday)
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
            ->method('withRecurringOnDayOfWeekFilter');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_for_an_invalid_day_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams(['recurringOnDayOfWeek' => 'someday']);

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('Unknown day of week value "someday"');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_the_hours_of_a_single_day_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams([
                'recurringOnDayOfWeek' => 'wednesday',
                'recurringOnLocalTimeFrom' => '1300',
                'recurringOnLocalTimeTo' => '1600',
            ]);

        // The hours replace the day of week filter, they do not sit next to it.
        $this->queryBuilder->expects($this->never())
            ->method('withRecurringOnDayOfWeekFilter');

        $this->queryBuilder->expects($this->once())
            ->method('withRecurringOnLocalTimeRangeFilter')
            ->with(1300, 1600, DayOfWeek::Wednesday)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_the_hours_of_multiple_days_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams([
                'recurringOnDayOfWeek' => 'wednesday,saturday',
                'recurringOnLocalTimeFrom' => '1300',
                'recurringOnLocalTimeTo' => '1600',
            ]);

        $this->queryBuilder->expects($this->once())
            ->method('withRecurringOnLocalTimeRangeFilter')
            ->with(1300, 1600, DayOfWeek::Wednesday, DayOfWeek::Saturday)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_accepts_midnight_as_the_start_of_the_hours(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams([
                'recurringOnDayOfWeek' => 'sunday',
                'recurringOnLocalTimeFrom' => '0000',
                'recurringOnLocalTimeTo' => '0200',
            ]);

        $this->queryBuilder->expects($this->once())
            ->method('withRecurringOnLocalTimeRangeFilter')
            ->with(0, 200, DayOfWeek::Sunday)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_when_the_hours_are_given_without_a_day_of_week(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams([
                'recurringOnLocalTimeFrom' => '1300',
                'recurringOnLocalTimeTo' => '1600',
            ]);

        $this->queryBuilder->expects($this->never())
            ->method('withRecurringOnLocalTimeRangeFilter');

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('require "recurringOnDayOfWeek"');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_when_only_the_start_of_the_hours_is_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams([
                'recurringOnDayOfWeek' => 'wednesday',
                'recurringOnLocalTimeFrom' => '1300',
            ]);

        $this->queryBuilder->expects($this->never())
            ->method('withRecurringOnLocalTimeRangeFilter');

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('"recurringOnLocalTimeTo"');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_when_only_the_end_of_the_hours_is_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams([
                'recurringOnDayOfWeek' => 'wednesday',
                'recurringOnLocalTimeTo' => '1600',
            ]);

        $this->queryBuilder->expects($this->never())
            ->method('withRecurringOnLocalTimeRangeFilter');

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('"recurringOnLocalTimeFrom"');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_for_an_invalid_day_of_week_when_the_hours_are_given(): void
    {
        $request = ServerRequestFactory::createFromGlobals()
            ->withQueryParams([
                'recurringOnDayOfWeek' => 'someday',
                'recurringOnLocalTimeFrom' => '1300',
                'recurringOnLocalTimeTo' => '1600',
            ]);

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('Unknown day of week value "someday"');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }
}
