<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ContributorsRequestParserTest extends TestCase
{
    private ContributorsRequestParser $parser;

    /**
     * @var OfferQueryBuilderInterface|MockObject
     */
    private $queryBuilder;

    protected function setUp()
    {
        $this->parser = new ContributorsRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_should_add_a_contributors_filter()
    {
        $request = $this->request(
            [
                'contributors' => 'info@example.com',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withContributorsFilter')
            ->with('info@example.com')
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    private function request(array $params): ApiRequest
    {
        $request = ServerRequestFactory::createFromGlobals();
        return new ApiRequest($request->withQueryParams($params));
    }
}
