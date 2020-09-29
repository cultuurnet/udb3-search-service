<?php

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use ValueObjects\Number\Natural;

class AgeRangeOfferRequestParserTest extends TestCase
{
    /**
     * @var DocumentLanguageOfferRequestParser
     */
    private $parser;

    /**
     * @var OfferQueryBuilderInterface|MockObject
     */
    private $queryBuilder;

    public function setUp()
    {
        $this->parser = new AgeRangeOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_should_add_an_age_range_filter_with_a_min_age()
    {

        $request = $this->request(
            [
                'minAge' => '7',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAgeRangeFilter')
            ->with(new Natural(7), null)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_an_age_range_filter_with_a_max_age()
    {
        $request = $this->request(
            [
                'maxAge' => '12',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAgeRangeFilter')
            ->with(null, new Natural(12))
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_an_age_range_filter_with_a_min_and_max_age()
    {
        $request = $this->request(
            [
                'minAge' => '7',
                'maxAge' => '12',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withAgeRangeFilter')
            ->with(new Natural(7), new Natural(12))
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_an_all_ages_filter()
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
