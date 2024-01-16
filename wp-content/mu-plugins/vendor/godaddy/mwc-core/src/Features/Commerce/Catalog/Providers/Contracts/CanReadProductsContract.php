<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductRequestInputs\ReadProductInput;

/**
 * Contract for providers that can read products.
 */
interface CanReadProductsContract
{
    /**
     * Reads a product from a corresponding input.
     *
     * @param ReadProductInput $input
     * @return ProductBase
     */
    public function read(ReadProductInput $input) : ProductBase;
}
