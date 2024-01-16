<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations;

use GoDaddy\WordPress\MWC\Common\Traits\CanGetNewInstanceTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts\ReadSummaryOperationContract;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

class ReadSummaryOperation implements ReadSummaryOperationContract
{
    use CanGetNewInstanceTrait;

    protected Product $product;

    /**
     * @param Product $product
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Gets the product.
     *
     * @return Product
     */
    public function getProduct() : Product
    {
        return $this->product;
    }
}
