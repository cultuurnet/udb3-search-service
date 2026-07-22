<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\Offer;

use CultuurNet\UDB3\Search\ElasticSearch\BirthdateRangeQueryStringParser;
use CultuurNet\UDB3\Search\Http\Offer\MatchingBirthdateRangesResolver;
use CultuurNet\UDB3\Search\Json;
use CultuurNet\UDB3\Search\Offer\BirthdateRange;
use CultuurNet\UDB3\Search\ReadModel\JsonDocument;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;

final class BirthdateRangeMatchingConsistencyTest extends TestCase
{
    private DateTimeImmutable $now;

    private BirthdateRange $queriedRange;

    protected function setUp(): void
    {
        $this->now = new DateTimeImmutable('2026-07-03');
        $this->queriedRange = new BirthdateRange(
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2022-12-31'),
            $this->now
        );
    }

    /**
     * @test
     * @dataProvider documentBodies
     * @param array<string, mixed> $body
     */
    public function the_filter_query_and_the_resolver_agree_on_the_same_document(bool $expectedMatch, array $body): void
    {
        $document = json_decode(Json::encode(array_merge(['@id' => 'event/1', '@type' => 'Event'], $body)));

        self::assertSame(
            $expectedMatch,
            $this->esFilterQueryMatches($document),
            'ElasticSearchOfferQueryBuilder::withBirthdateRangeFilter() disagrees with the fixture'
        );

        self::assertSame(
            $expectedMatch,
            $this->resolverMatches($document),
            'MatchingBirthdateRangesResolver::match() disagrees with the fixture'
        );
    }

    public function documentBodies(): array
    {
        return [
            'birthdate range touches the upper boundary exactly' => [
                true,
                ['birthdateRange' => ['gte' => '2022-12-31', 'lte' => '2023-06-30']],
            ],
            'birthdate range starts one day after the upper boundary' => [
                false,
                ['birthdateRange' => ['gte' => '2023-01-01', 'lte' => '2023-06-30']],
            ],
            'birthdate range touches the lower boundary exactly' => [
                true,
                ['birthdateRange' => ['gte' => '2019-06-01', 'lte' => '2020-01-01']],
            ],
            'birthdate range ends one day before the lower boundary' => [
                false,
                ['birthdateRange' => ['gte' => '2019-06-01', 'lte' => '2019-12-31']],
            ],
            'open-ended birthdate range starting before the upper boundary' => [
                true,
                ['birthdateRange' => ['gte' => '2021-01-01']],
            ],
            'open-ended birthdate range starting after the upper boundary' => [
                false,
                ['birthdateRange' => ['gte' => '2023-01-01']],
            ],
            'typical age range touches the upper age boundary exactly (age 6)' => [
                true,
                ['typicalAgeRange' => ['gte' => 6, 'lte' => 8], 'allAges' => false],
            ],
            'typical age range starts one year above the upper age boundary' => [
                false,
                ['typicalAgeRange' => ['gte' => 7, 'lte' => 8], 'allAges' => false],
            ],
            'typical age range touches the lower age boundary exactly (age 3)' => [
                true,
                ['typicalAgeRange' => ['gte' => 1, 'lte' => 3], 'allAges' => false],
            ],
            'typical age range ends one year below the lower age boundary' => [
                false,
                ['typicalAgeRange' => ['gte' => 1, 'lte' => 2], 'allAges' => false],
            ],
            'all ages is excluded even though the age range would otherwise match' => [
                false,
                ['typicalAgeRange' => ['gte' => 3, 'lte' => 6], 'allAges' => true],
            ],
            'neither field is present' => [
                false,
                [],
            ],
        ];
    }

    private function esFilterQueryMatches(stdClass $document): bool
    {
        $builtQuery = (new ElasticSearchOfferQueryBuilder())
            ->withBirthdateRangeFilter($this->queriedRange)
            ->build();
        $rangeQuery = $builtQuery['query']['bool']['filter'][0]['bool'];

        $birthdateBounds = $rangeQuery['should'][0]['range']['birthdateRange'];
        $birthdateMatches = $this->rangeFieldIntersectsQueryBounds(
            $document->birthdateRange->gte ?? null,
            $document->birthdateRange->lte ?? null,
            $birthdateBounds['gte'] ?? null,
            $birthdateBounds['lte'] ?? null
        );

        $ageBounds = $rangeQuery['should'][1]['bool']['must'][0]['range']['typicalAgeRange'];
        $ageMatches = ($document->allAges ?? false) !== true
            && $this->rangeFieldIntersectsQueryBounds(
                $document->typicalAgeRange->gte ?? null,
                $document->typicalAgeRange->lte ?? null,
                $ageBounds['gte'] ?? null,
                $ageBounds['lte'] ?? null
            );

        return $birthdateMatches || $ageMatches;
    }

    /**
     * @param string|int|null $docFrom
     * @param string|int|null $docTo
     * @param string|int|null $queryFrom
     * @param string|int|null $queryTo
     */
    private function rangeFieldIntersectsQueryBounds($docFrom, $docTo, $queryFrom, $queryTo): bool
    {
        if ($docFrom === null && $docTo === null) {
            return false;
        }
        if ($queryFrom !== null && $docTo !== null && $docTo < $queryFrom) {
            return false;
        }
        if ($queryTo !== null && $docFrom !== null && $docFrom > $queryTo) {
            return false;
        }
        return true;
    }

    private function resolverMatches(stdClass $document): bool
    {
        $resolver = new MatchingBirthdateRangesResolver(new BirthdateRangeQueryStringParser(), $this->now);
        $result = $resolver->match([$this->queriedRange], [new JsonDocument('event/1', Json::encode($document))]);

        return in_array('event/1', $result[0]['matches'], true);
    }
}
