<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\SimpleArrayLogger;
use CultuurNet\UDB3\Search\JsonDocument\JsonTransformerPsrLogger;
use PHPUnit\Framework\TestCase;

final class SubEventCapTransformerTest extends TestCase
{
    private const CAP = 5;

    private SimpleArrayLogger $simpleArrayLogger;

    private SubEventCapTransformer $transformer;

    protected function setUp(): void
    {
        $this->simpleArrayLogger = new SimpleArrayLogger();
        $this->transformer = new SubEventCapTransformer(
            new JsonTransformerPsrLogger($this->simpleArrayLogger),
            self::CAP
        );
    }

    /**
     * @test
     */
    public function it_does_nothing_when_draft_has_no_sub_event(): void
    {
        $draft = ['calendarType' => 'single'];

        $result = $this->transformer->transform([], $draft);

        $this->assertSame($draft, $result);
        $this->assertSame([], $this->simpleArrayLogger->getLogs());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_count_is_under_the_cap(): void
    {
        $subEvent = array_fill(0, self::CAP - 1, ['status' => 'Available']);
        $draft = ['subEvent' => $subEvent];

        $result = $this->transformer->transform([], $draft);

        $this->assertSame($draft, $result);
        $this->assertSame([], $this->simpleArrayLogger->getLogs());
    }

    /**
     * @test
     */
    public function it_does_nothing_when_the_count_is_exactly_at_the_cap(): void
    {
        $subEvent = array_fill(0, self::CAP, ['status' => 'Available']);
        $draft = ['subEvent' => $subEvent];

        $result = $this->transformer->transform([], $draft);

        $this->assertSame($draft, $result);
        $this->assertSame([], $this->simpleArrayLogger->getLogs());
    }

    /**
     * @test
     */
    public function it_truncates_and_logs_a_warning_when_the_count_is_over_the_cap(): void
    {
        $subEvent = array_fill(0, self::CAP + 1, ['status' => 'Available']);
        $draft = ['subEvent' => $subEvent];

        $result = $this->transformer->transform(['@id' => 'http://example.com/event/1'], $draft);

        $this->assertSame(array_fill(0, self::CAP, ['status' => 'Available']), $result['subEvent']);
        $this->assertSame(
            [
                ['warning', 'subEvent truncated from 6 to 5 entries for http://example.com/event/1.', []],
            ],
            $this->simpleArrayLogger->getLogs()
        );
    }

    /**
     * @test
     */
    public function it_uses_unknown_as_the_id_when_the_source_has_no_id(): void
    {
        $subEvent = array_fill(0, self::CAP + 1, ['status' => 'Available']);
        $draft = ['subEvent' => $subEvent];

        $this->transformer->transform([], $draft);

        $this->assertSame(
            [
                ['warning', 'subEvent truncated from 6 to 5 entries for unknown.', []],
            ],
            $this->simpleArrayLogger->getLogs()
        );
    }
}
