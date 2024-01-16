<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Events\Transformers;

use GoDaddy\WordPress\MWC\Common\Events\AbstractEventTransformer;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\Marketplaces\Handlers\MerchantProvisioningHandler;

/**
 * Adds the isMerchantProvisionedOnGDM flag to product events.
 */
class MerchantProvisionedTransformer extends AbstractEventTransformer
{
    /**
     * {@inheritDoc}
     */
    public function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof EventBridgeEventContract && 'product' === $event->getResource();
    }

    /**
     * {@inheritDoc}
     */
    public function handle(EventContract $event) : void
    {
        // sanity check to prevent phpstan warnings
        if ($event instanceof EventBridgeEventContract) {
            $data = $event->getData();

            $isMerchantProvisionedOnGDM = MerchantProvisioningHandler::isMerchantProvisioned() ? 'yes' : 'no';

            ArrayHelper::set($data, 'isMerchantProvisionedOnGDM', $isMerchantProvisionedOnGDM);

            $event->setData($data);
        }
    }
}
