<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http\Offer\RequestParser;

use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Search\Http\ApiRequest;
use CultuurNet\UDB3\Search\MissingParameter;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\Offer\OfferQueryBuilderInterface;
use CultuurNet\UDB3\Search\UnsupportedParameterValue;
use DateTimeImmutable;
use DateTimeInterface;
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
        Chronos::setTestNow(Chronos::createFromFormat(DateTimeInterface::ATOM, '2026-06-29T12:00:00+02:00'));

        $this->parser = new BirthdateRangeOfferRequestParser();
        $this->queryBuilder = $this->createMock(OfferQueryBuilderInterface::class);
    }

    protected function tearDown(): void
    {
        Chronos::setTestNow(null);
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_parameters_are_absent(): void
    {
        $request = $this->request([]);

        $this->queryBuilder->expects($this->never())->method('withBirthdateRangeFilter');

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_adds_a_birthdate_range_filter(): void
    {
        $request = $this->request([
            'birthdateRangeFrom' => '2020-01-01',
            'birthdateRangeTo' => '2020-12-31',
        ]);

        $expected = new BirthdateRange(
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-12-31'),
            new Chronos()
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
    public function it_throws_when_only_the_from_is_given(): void
    {
        $request = $this->request(['birthdateRangeFrom' => '2020-01-01']);

        $this->expectException(MissingParameter::class);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     */
    public function it_throws_when_only_the_to_is_given(): void
    {
        $request = $this->request(['birthdateRangeTo' => '2020-12-31']);

        $this->expectException(MissingParameter::class);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @test
     * @dataProvider invalidDateProvider
     */
    public function it_throws_when_a_range_bound_is_not_a_valid_date(string $invalidDate): void
    {
        $request = $this->request([
            'birthdateRangeFrom' => '2020-01-01',
            'birthdateRangeTo' => $invalidDate,
        ]);

        $this->expectException(UnsupportedParameterValue::class);

        $this->parser->parse($request, $this->queryBuilder);
    }

    /**
     * @return array<string, array{string}>
     */
    public function invalidDateProvider(): array
    {
        return [
            'not a date' => ['not-a-date'],
            'trailing garbage' => ['2020-12-31abc'],
            'trailing garbage after valid date' => ['2020-01-01garbage'],
            'month and day overflow' => ['2020-13-45'],
            'day overflow' => ['2020-02-30'],
            'month not zero-padded' => ['2020-1-01'],
            'day not zero-padded' => ['2020-01-1'],
        ];
    }

    private function request(array $params): ApiRequest
    {
        $request = ServerRequestFactory::createFromGlobals();
        return new ApiRequest($request->withQueryParams($params));
    }
}
