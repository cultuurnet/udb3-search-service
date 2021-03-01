<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search\Http;

use CultuurNet\UDB3\Search\Http\Parameters\ParameterBagInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface ApiRequestInterface
 *
 * Custom interface that adds some additional
 * helper methods to the request.
 *
 * @package CultuurNet\UDB3\Search\Http
 */
interface ApiRequestInterface extends ServerRequestInterface
{
    public function getQueryParam(string $name, $default = null);
    
    public function hasQueryParam(string $name): bool;
    
    public function getQueryParamsKeys(): ?array;

    public function getQueryParameterBag(): ParameterBagInterface;
    
    public function getServerParam(string $name, $default = null);
}
