<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Contracts\CommerceProductDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\Traits\HasProductPlatformDataStoreCrudTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use WC_Product_Variation_Data_Store_CPT;

/**
 * Commerce Catalog products data store for variant products.
 *
 * A WooCommerce data store for product variations to replace the default data store to enable read and write operations with the Commerce API.
 */
class ProductVariationDataStore extends WC_Product_Variation_Data_Store_CPT implements CommerceProductDataStoreContract
{
    use HasProductPlatformDataStoreCrudTrait;

    /**
     * Constructs the data store.
     *
     * @param ProductsServiceContract $productsService
     */
    public function __construct(ProductsServiceContract $productsService)
    {
        $this->productsService = $productsService;
    }
}
