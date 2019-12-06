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
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerReadRepositoryInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerWriteRepositoryInterface;
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
        ConsumerWriteRepositoryInterface::class,
        ConsumerReadRepositoryInterface::class,
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
                    $this->parameter('uitid.consumer.key'),
                    $this->parameter('uitid.consumer.secret')
                );

                $oauthClient = new \CultureFeed_DefaultOAuthClient(
                    $consumerCredentials->getKey(),
                    $consumerCredentials->getSecret()
                );
                $oauthClient->setEndpoint($this->parameter('uitid.base_url'));

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

        $this->addShared(
            ConsumerReadRepositoryInterface::class,
            function () {
                return $this->get(InMemoryConsumerRepository::class);
            }
        );

        $this->addShared(
            ConsumerWriteRepositoryInterface::class,
            function () {
                return $this->get(InMemoryConsumerRepository::class);
            }
        );

        $this->addShared(
            InMemoryConsumerRepository::class,
            function () {
                return new InMemoryConsumerRepository();
            }
        );
    }
}
