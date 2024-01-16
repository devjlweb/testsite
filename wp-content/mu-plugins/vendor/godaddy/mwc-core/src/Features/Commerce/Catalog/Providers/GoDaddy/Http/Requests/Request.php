<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\GoDaddy\Http\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Common\Platforms\PlatformEnvironment;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Requests\AbstractRequest;

/**
 * Request class for communicating with the Commerce Catalog API.
 */
class Request extends AbstractRequest
{
    /** @var string */
    const ENVIRONMENT_TEST = 'TEST';

    /** @var string */
    const ENVIRONMENT_PRODUCTION = 'PROD';

    /**
     * Constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        if (static::ENVIRONMENT_TEST === $this->getEnvironment()) {
            $timeout = Configuration::get('commerce.catalog.api.timeout.dev');
        } else {
            $timeout = Configuration::get('commerce.catalog.api.timeout.prod');
        }

        $this->setTimeout(TypeHelper::int($timeout, 10));
    }

    /**
     * Determines the environment the request should consider.
     *
     * @return string
     */
    protected function getEnvironment() : string
    {
        $environment = ManagedWooCommerceRepository::getEnvironment();

        return in_array($environment, [PlatformEnvironment::TEST, PlatformEnvironment::LOCAL], true)
            ? static::ENVIRONMENT_TEST
            : static::ENVIRONMENT_PRODUCTION;
    }

    /**
     * {@inheritDoc}
     */
    protected function getBaseUrl() : string
    {
        if (static::ENVIRONMENT_TEST === $this->getEnvironment()) {
            $apiUrl = Configuration::get('commerce.catalog.api.url.dev');
        } else {
            $apiUrl = Configuration::get('commerce.catalog.api.url.prod');
        }

        return TypeHelper::string($apiUrl, '');
    }

    /**
     * Builds a valid url string with parameters.
     *
     * @return string
     * @throws Exception
     */
    public function buildUrlString() : string
    {
        /*
         * unset the locale to prevent a `locale` query arg from being added
         * this can be removed after decoupling from {@see GoDaddyRequest::buildUrlString()}
         */
        $this->locale = '';

        return parent::buildUrlString();
    }

    /**
     * This path is required when using the proxy as the $apiUrl.
     */
    protected function getPathPrefix() : string
    {
        return '/v1/commerce/proxy/stores/'.$this->storeId;
    }
}
