<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Contract to define default signatures for read {@see Summary} operations.
 */
interface ReadSummaryOperationContract
{
    /**
     * Gets the {@see Product} that will have the summary reading in this operation.
     *
     * @return Product
     */
    public function getProduct() : Product;
}
