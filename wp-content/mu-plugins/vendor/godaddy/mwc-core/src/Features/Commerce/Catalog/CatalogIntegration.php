<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\AbstractIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CategoryReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CategoryWritesInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CommerceProductUuidRequestInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CostOfGoodsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CreateOrUpdateRemoteVariantsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\CrossSellProductsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ListRemoteVariantsJobInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\LocalProductDeletedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\PrimePostCachesInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductEditInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductReadInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductTrashedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductUntrashedInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\ProductVariationDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\RelatedProductsInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\UpdateProductMetaCacheInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\VariableProductDataStoreInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\WpQueryInterceptor;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Traits\IntegrationEnabledOnTestTrait;

/**
 * Commerce Catalog integration class.
 */
class CatalogIntegration extends AbstractIntegration
{
    use IntegrationEnabledOnTestTrait;

    /** @var string the name of the integration */
    public const NAME = 'catalog';

    /** @var string Product category taxonomy name */
    public const PRODUCT_CATEGORY_TAXONOMY = 'product_cat';

    /** @var string WooCommerce product post type name */
    public const PRODUCT_POST_TYPE = 'product';

    /** @var string WooCommerce variable product post type name */
    public const VARIABLE_PRODUCT_POST_TYPE = 'product_variation';

    /** @var class-string[] alphabetically ordered list of components to load */
    protected array $componentClasses = [
        CategoryReadInterceptor::class,
        CategoryWritesInterceptor::class,
        CreateOrUpdateRemoteVariantsInterceptor::class,
        CrossSellProductsInterceptor::class,
        LocalProductDeletedInterceptor::class,
        ProductReadInterceptor::class,
        UpdateProductMetaCacheInterceptor::class,
        ProductDataStoreInterceptor::class,
        ProductVariationDataStoreInterceptor::class,
        VariableProductDataStoreInterceptor::class,
        PrimePostCachesInterceptor::class,
        WpQueryInterceptor::class,
        CostOfGoodsInterceptor::class,
        ProductEditInterceptor::class,
        ListRemoteVariantsJobInterceptor::class,
        ProductTrashedInterceptor::class,
        ProductUntrashedInterceptor::class,
        CommerceProductUuidRequestInterceptor::class,
        RelatedProductsInterceptor::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getIntegrationName() : string
    {
        return self::NAME;
    }
}
