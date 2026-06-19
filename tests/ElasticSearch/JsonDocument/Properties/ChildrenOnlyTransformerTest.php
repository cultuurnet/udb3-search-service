<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use PHPUnit\Framework\TestCase;

final class ChildrenOnlyTransformerTest extends TestCase
{
    private ChildrenOnlyTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new ChildrenOnlyTransformer();
    }

    /**
     * @test
     */
    public function it_sets_childrenOnly_to_true_when_source_has_true(): void
    {
        $result = $this->transformer->transform(['childrenOnly' => true]);

        $this->assertSame(['childrenOnly' => true], $result);
    }

    /**
     * @test
     */
    public function it_omits_childrenOnly_when_source_has_false(): void
    {
        $result = $this->transformer->transform(['childrenOnly' => false]);

        $this->assertArrayNotHasKey('childrenOnly', $result);
    }

    /**
     * @test
     */
    public function it_omits_childrenOnly_when_source_field_is_missing(): void
    {
        $result = $this->transformer->transform([]);

        $this->assertArrayNotHasKey('childrenOnly', $result);
    }

    /**
     * @test
     */
    public function it_preserves_other_draft_fields(): void
    {
        $result = $this->transformer->transform(
            ['childrenOnly' => true],
            ['audienceType' => 'everyone']
        );

        $this->assertSame(
            ['audienceType' => 'everyone', 'childrenOnly' => true],
            $result
        );
    }
}
