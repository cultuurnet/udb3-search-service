<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Organizer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Organizer\OrganizerQueryBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class IdRequestParserTest extends TestCase
{
    private IdRequestParser $parser;

    /**
     * @var OrganizerQueryBuilderInterface|MockObject
     */
    private $queryBuilder;

    private string $organizerId;

    public function setUp(): void
    {
        $this->organizerId = '3f042019-9516-46b7-b113-35324d90a534';
        $this->parser = new IdRequestParser();
        $this->queryBuilder = $this->createMock(OrganizerQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_should_add_an_id_filter(): void
    {
        $request = $this->request(
            [
                'id' => $this->organizerId,
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withIdFilter')
            ->with($this->organizerId)
            ->willReturn($this->queryBuilder);

        $this->parser->parse(new ApiRequest($request), $this->queryBuilder);
    }

    private function request(array $params): ApiRequest
    {
        $request = ServerRequestFactory::createFromGlobals();
        return new ApiRequest($request->withQueryParams($params));
    }
}
