<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\AttendanceMode;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AttendanceModeOfferRequestParserTest extends TestCase
{
    private AttendanceModeOfferRequestParser $parser;

    /**
     * @var OfferQueryBuilderInterface&MockObject
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->parser = new AttendanceModeOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_adds_a_single_attendanceMode(): void
    {
        $request = ServerRequestFactory::createFromGlobals();
        $request = $request->withQueryParams(
            [
                'attendanceMode' => 'mixed',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAttendanceModeFilter')
            ->with(AttendanceMode::mixed())
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_multiple_attendanceModes(): void
    {
        $request = ServerRequestFactory::createFromGlobals();
        $request = $request->withQueryParams(
            [
                'attendanceMode' => 'offline,mixed',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAttendanceModeFilter')
            ->with(AttendanceMode::offline(), AttendanceMode::mixed())
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_for_invalid_attendanceMode(): void
    {
        $request = ServerRequestFactory::createFromGlobals();
        $request = $request->withQueryParams(
            [
                'attendanceMode' => 'virtual',
            ]
        );

        $this->expectException(UnsupportedParameterValue::class);
        $this->expectExceptionMessage('Unknown attendance mode value "virtual"');

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }
}
