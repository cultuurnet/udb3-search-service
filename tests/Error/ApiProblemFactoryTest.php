<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Error;

use CultuurNet\UDB3\Search\ElasticSearch\Client\ElasticSearchRequestFailed;
use CultuurNet\UDB3\SearchService\Error\ApiProblemFactory;
use PHPUnit\Framework\TestCase;

final class ApiProblemFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_converts_an_unparsable_query_failure_into_an_unsupported_parameter_problem(): void
    {
        $failure = $this->failureWithReason(400, 'Failed to parse query [foo:]');

        $problem = ApiProblemFactory::createFromThrowable($failure);

        $this->assertSame(404, $problem->getStatus());
        $this->assertSame(
            'Could not parse query given "q" parameter as a valid Lucene query.',
            $problem->getDetail()
        );
    }

    /**
     * @test
     */
    public function it_converts_any_other_failure_into_an_internal_server_error_with_the_reason(): void
    {
        $failure = $this->failureWithReason(500, 'shard failure');

        $problem = ApiProblemFactory::createFromThrowable($failure);

        $this->assertSame(500, $problem->getStatus());
        $this->assertSame('Elasticsearch error: shard failure', $problem->getDetail());
    }

    /**
     * @test
     */
    public function it_falls_back_to_the_message_when_the_failure_has_no_reason(): void
    {
        $failure = new ElasticSearchRequestFailed('502 Bad Gateway: <html>Bad Gateway</html>', 502, []);

        $problem = ApiProblemFactory::createFromThrowable($failure);

        $this->assertSame(500, $problem->getStatus());
        $this->assertSame('Elasticsearch error: 502 Bad Gateway: <html>Bad Gateway</html>', $problem->getDetail());
    }

    private function failureWithReason(int $status, string $reason): ElasticSearchRequestFailed
    {
        return new ElasticSearchRequestFailed(
            'Elasticsearch responded with ' . $status,
            $status,
            ['error' => ['root_cause' => [['reason' => $reason]]]]
        );
    }
}
