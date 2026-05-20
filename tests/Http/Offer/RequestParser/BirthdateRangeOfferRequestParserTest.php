<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class BirthdateRangeOfferRequestParserTest extends TestCase
{
    private BirthdateRangeOfferRequestParser $parser;

    /**
     * @var OfferQueryBuilderInterface&MockObject
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->parser = new BirthdateRangeOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_parameter_is_absent(): void
    {
        $request = $this->request([]);

        $this->queryBuilder->expects($this->never())->method('withBirthdateRangeFilter');

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_a_single_birthdate_range_filter(): void
    {
        $request = $this->request(['birthdateRange' => '2020-01-01..2020-12-31']);

        $expected = new BirthdateRange(
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-12-31')
        );

        $this->queryBuilder->expects($this->once())
            ->method('withBirthdateRangeFilter')
            ->with($this->equalTo($expected))
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_multiple_birthdate_range_filters_split_on_comma(): void
    {
        $request = $this->request(
            ['birthdateRange' => '2020-01-01..2020-12-31,2022-06-30..2022-12-31']
        );

        $first = new BirthdateRange(
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-12-31')
        );
        $second = new BirthdateRange(
            new DateTimeImmutable('2022-06-30'),
            new DateTimeImmutable('2022-12-31')
        );

        $this->queryBuilder->expects($this->once())
            ->method('withBirthdateRangeFilter')
            ->with($this->equalTo($first), $this->equalTo($second))
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_when_a_range_is_missing_the_separator(): void
    {
        $request = $this->request(['birthdateRange' => '2020-01-01']);

        $this->expectException(UnsupportedParameterValue::class);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_when_a_range_bound_is_not_a_valid_date(): void
    {
        $request = $this->request(['birthdateRange' => '2020-01-01..not-a-date']);

        $this->expectException(UnsupportedParameterValue::class);

        $this->parser->parse($request, $this->queryBuilder);
    }

    private function request(array $params): ApiRequest
    {
        $request = ServerRequestFactory::createFromGlobals();
        return new ApiRequest($request->withQueryParams($params));
    }
}
