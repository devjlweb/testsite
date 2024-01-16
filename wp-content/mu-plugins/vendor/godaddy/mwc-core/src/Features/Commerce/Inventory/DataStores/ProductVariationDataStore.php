<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores;

use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\DataStores\ProductVariationDataStore as CatalogProductVariationDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\DataStores\Traits\CanCrudPlatformInventoryDataTrait;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\Contracts\InventoryProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\LevelsServiceWithCacheContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Models\Contracts\CommerceContextContract;

class ProductVariationDataStore extends CatalogProductVariationDataStore
{
    use CanCrudPlatformInventoryDataTrait;

    /**
     * @param ProductsServiceContract $productsService
     * @param LevelsServiceContract $levelsService
     * @param LevelsServiceWithCacheContract $levelsServiceWithCache
     * @param SummariesServiceContract $summariesService
     * @param InventoryProviderContract $inventoryProvider
     * @param CommerceContextContract $commerceContext
     */
    public function __construct(
        ProductsServiceContract $productsService,
        LevelsServiceContract $levelsService,
        LevelsServiceWithCacheContract $levelsServiceWithCache,
        SummariesServiceContract $summariesService,
        InventoryProviderContract $inventoryProvider,
        CommerceContextContract $commerceContext
    ) {
        $this->levelsService = $levelsService;
        $this->levelsServiceWithCache = $levelsServiceWithCache;
        $this->summariesService = $summariesService;
        $this->inventoryProvider = $inventoryProvider;
        $this->commerceContext = $commerceContext;

        parent::__construct($productsService);
    }
}
