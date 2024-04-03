<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\Language\Language;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class DocumentLanguageOfferRequestParserTest extends TestCase
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
        $this->parser = new DocumentLanguageOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    /**
     * @test
     */
    public function it_should_add_a_main_language_filter(): void
    {
        $request = $this->request(
            [
                'mainLanguage' => 'nl',
            ]
        );

        $this->queryBuilder->expects($this->once())
            ->method('withMainLanguageFilter')
            ->with(new Language('nl'))
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_language_filters(): void
    {
        $request = $this->request(
            [
                'languages' => ['nl', 'fr', 'de'],
            ]
        );

        $this->queryBuilder->expects($this->exactly(3))
            ->method('withLanguageFilter')
            ->withConsecutive(
                [new Language('nl')],
                [new Language('fr')],
                [new Language('de')]
            )
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_should_add_completed_language_filters(): void
    {
        $request = $this->request(
            [
                'completedLanguages' => ['nl', 'fr', 'de'],
            ]
        );

        $this->queryBuilder->expects($this->exactly(3))
            ->method('withCompletedLanguageFilter')
            ->withConsecutive(
                [new Language('nl')],
                [new Language('fr')],
                [new Language('de')]
            )
            ->willReturn($this->queryBuilder);

        $this->parser->parse($request, $this->queryBuilder);
    }

    private function request(array $params): ApiRequest
    {
        $request = ServerRequestFactory::createFromGlobals();
        return new ApiRequest($request->withQueryParams($params));
    }
}
