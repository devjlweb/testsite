<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\GraphQL\Requests;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Http\GraphQL\Contracts\RequestContract;
use GoDaddy\WordPress\MWC\Common\Http\GraphQL\Request;
use GoDaddy\WordPress\MWC\Common\Http\Traits\CanSetAuthMethodTrait;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits\HasManagedWooCommerceAuthProviderTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits\IsAuthenticatedGraphQLRequestTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Providers\Http\Traits\IsCommerceRequestTrait;

abstract class AbstractRequest extends Request implements RequestContract
{
    use IsAuthenticatedGraphQLRequestTrait;
    use CanGetNewInstanceTrait;
    use CanSetAuthMethodTrait;
    use HasManagedWooCommerceAuthProviderTrait;
    use IsCommerceRequestTrait {
        setStoreId as private traitSetStoreId;
    }

    /**
     * {@inheritDoc}
     *
     * @return $this
     */
    public function setStoreId(string $value)
    {
        try {
            $this->addHeaders([
                'storeId' => $value,
            ]);
        } catch (Exception $e) {
            // Ignore.
        }

        return $this->traitSetStoreId($value);
    }
}
