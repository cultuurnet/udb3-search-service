<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch;

trait ElasticSearch5Compatibility
{
    protected bool $compatibilityMode = false;

    public function enableElasticSearch5CompatibilityMode(): static
    {
        $this->compatibilityMode = true;
        return $this;
    }

    protected function usesCompatibilityMode(): bool
    {
        return $this->compatibilityMode;
    }

    protected function usesDocumentTypes(): bool
    {
        return $this->compatibilityMode;
    }

}
