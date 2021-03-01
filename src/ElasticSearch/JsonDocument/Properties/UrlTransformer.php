<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\JsonDocument\JsonTransformer;
use Stringy\Stringy;
use ValueObjects\Web\Url;

final class UrlTransformer implements JsonTransformer
{
    public function transform(array $from, array $draft = []): array
    {
        if (!isset($from['url'])) {
            return $draft;
        }

        $draft['url'] = $from['url'];
        $draft['domain'] = $this->extractDomain($from['url']);
        return $draft;
    }

    private function extractDomain(string $url): string
    {
        $url = Url::fromNative($url);
        $domain = $url->getDomain();

        $domain = Stringy::create($domain);
        $domain = $domain->removeLeft('www.');

        return (string) $domain;
    }
}
