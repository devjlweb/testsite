<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Http\Requests;

use GoDaddy\WordPress\MWC\Common\Platforms\PlatformRepositoryFactory;

/**
 * API request to update a GDM merchant.
 */
class UpdateMerchantRequest extends ProvisionMerchantRequest
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->route = 'merchants/'.PlatformRepositoryFactory::getNewInstance()->getPlatformRepository()->getVentureId();

        parent::__construct();

        $this->setMethod('PUT');
    }
}
