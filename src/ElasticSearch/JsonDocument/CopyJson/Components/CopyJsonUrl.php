<?php

namespace CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\Components;

use CultuurNet\UDB3\Search\ElasticSearch\JsonDocument\CopyJson\CopyJsonInterface;
use function property_exists;
use Stringy\Stringy;
use function strpos;
use ValueObjects\Web\Url;

class CopyJsonUrl implements CopyJsonInterface
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
