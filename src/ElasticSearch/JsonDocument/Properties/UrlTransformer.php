<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\Properties;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use Stringy\Stringy;
use ValueObjects\Web\Url;
use function property_exists;

class UrlTransformer implements CopyJsonInterface
{
    public function copy(\stdClass $from, \stdClass $to)
    {
        if (!property_exists($from, 'url')) {
            return;
        }

        $to->url = $from->url;

        $to->domain = $this->extractDomain($from->url);
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
