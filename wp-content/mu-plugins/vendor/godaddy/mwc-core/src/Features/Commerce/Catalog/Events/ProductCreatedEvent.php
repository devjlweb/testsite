<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * An event that is fired when a product is created in the remote platform.
 *
 * @method static static getNewInstance(Product $product, string $remoteProductId)
 */
class ProductCreatedEvent implements EventContract
{
    use CanGetNewInstanceTrait;

    /** @var Product local product */
    public Product $product;

    /** @var string ID of the product in the remote platform */
    public string $remoteProductId;

    public function __construct(Product $product, string $remoteProductId)
    {
        $this->product = $product;
        $this->remoteProductId = $remoteProductId;
    }
}
