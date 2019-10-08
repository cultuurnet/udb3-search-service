<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SearchService\ApiKey;

use CultureFeed;
use CultuurNet\Auth\ConsumerCredentials;
use CultuurNet\UDB3\ApiGuard\ApiKey\ApiKeyAuthenticatorInterface;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\ApiKeyReaderInterface;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CompositeApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\CustomHeaderApiKeyReader;
use CultuurNet\UDB3\ApiGuard\ApiKey\Reader\QueryParameterApiKeyReader;
use CultuurNet\UDB3\ApiGuard\Consumer\InMemoryConsumerRepository;
use CultuurNet\UDB3\ApiGuard\CultureFeed\CultureFeedApiKeyAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\ApiKeyRequestAuthenticator;
use CultuurNet\UDB3\ApiGuard\Request\RequestAuthenticatorInterface;
use CultuurNet\UDB3\SearchService\BaseServiceProvider;

final class ApiGuardServiceProvider extends BaseServiceProvider
{
    protected $provides = [
        ApiKeyReaderInterface::class,
        ApiKeyAuthenticatorInterface::class,
        RequestAuthenticatorInterface::class,
        InMemoryConsumerRepository::class,
    ];

    public function register()
    {
        $this->add(
            ApiKeyReaderInterface::class,
            function () {
                $queryReader = new QueryParameterApiKeyReader('apiKey');
                $headerReader = new CustomHeaderApiKeyReader('X-Api-Key');

                return new CompositeApiKeyReader(
                    $queryReader,
                    $headerReader
                );
            }
        );

        $this->add(
            ApiKeyAuthenticatorInterface::class,
            function () {
                $consumerCredentials = new ConsumerCredentials(
                    $this->parameter('culturefeed.consumer.key'),
                    $this->parameter('culturefeed.consumer.secret')
                );

                $oauthClient = new \CultureFeed_DefaultOAuthClient(
                    $consumerCredentials->getKey(),
                    $consumerCredentials->getSecret()
                );
                $oauthClient->setEndpoint($this->parameter('culturefeed.endpoint'));

                return new CultureFeedApiKeyAuthenticator(
                    new CultureFeed($oauthClient),
                    $this->get(InMemoryConsumerRepository::class),
                    true
                );
            }
        );

        $this->add(
            RequestAuthenticatorInterface::class,
            function () {
                return new ApiKeyRequestAuthenticator(
                    $this->get(ApiKeyReaderInterface::class),
                    $this->get(ApiKeyAuthenticatorInterface::class)
                );
            }
        );

        $this->add(
            InMemoryConsumerRepository::class,
            function () {
                return new InMemoryConsumerRepository();
            }
        );
    }
}
