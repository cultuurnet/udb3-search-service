<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\Age;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AgeRangeOfferRequestParserTest extends TestCase
{
    /**
     * @var DocumentLanguageOfferRequestParser
     */
    private $parser;

    /**
     * @var OfferQueryBuilderInterface|MockObject
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->parser = new AgeRangeOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_should_add_an_age_range_filter_with_a_min_age(): void
    {
        $request = $this->request(
            [
                'minAge' => '7',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAgeRangeFilter')
            ->with(new Age(7), null)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_an_age_range_filter_with_a_max_age(): void
    {
        $request = $this->request(
            [
                'maxAge' => '12',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAgeRangeFilter')
            ->with(null, new Age(12))
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_an_age_range_filter_with_a_min_and_max_age(): void
    {
        $request = $this->request(
            [
                'minAge' => '7',
                'maxAge' => '12',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAgeRangeFilter')
            ->with(new Age(7), new Age(12))
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_an_all_ages_filter(): void
    {
        $request = $this->request(
            [
                'allAges' => 'true',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAllAgesFilter')
            ->with(true)
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    private function request(array $params): ApiRequest
    {
        $request = ServerRequestFactory::createFromGlobals();
        return new ApiRequest($request->withQueryParams($params));
    }
}
