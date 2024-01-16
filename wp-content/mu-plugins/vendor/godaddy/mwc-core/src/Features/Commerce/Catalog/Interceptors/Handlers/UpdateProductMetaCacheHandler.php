<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Interceptors\Handlers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\TypeHelper;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductBase;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataSources\Adapters\ProductPostMetaAdapter;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Commerce;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\InventoryIntegration;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Providers\DataObjects\Summary;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Contracts\SummariesServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Inventory\Services\Operations\ListSummariesOperation;
use GoDaddy\WordPress\MWC\Core\Interceptors\Handlers\AbstractInterceptorHandler;

/**
 * Update product meta cache handler.
 *
 * This handler will update the local product post meta cache with remote product metadata.
 *
 * @TODO in the future try to decouple the inventory logic MWC-12698 {agibson 2023-06-14)
 */
class UpdateProductMetaCacheHandler extends AbstractInterceptorHandler
{
    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productService;

    protected SummariesServiceContract $summariesService;

    /**
     * Constructor.
     *
     * @param ProductsServiceContract $productService
     * @param SummariesServiceContract $summariesService
     */
    public function __construct(
        ProductsServiceContract $productService,
        SummariesServiceContract $summariesService
    ) {
        $this->productService = $productService;
        $this->summariesService = $summariesService;
    }

    /**
     * Updates local product meta cache with remote product meta.
     *
     * @param array<int, mixed> $args hook arguments
     * @return array<int, array<string, array<mixed>>> cache data
     */
    public function run(...$args) : array
    {
        /** @var array<int, array<string, array<mixed>>> $cache */
        $cache = TypeHelper::array($args[0] ?? [], []);
        $objectIds = TypeHelper::arrayOfIntegers($args[1] ?? []);
        $metaType = TypeHelper::string($args[2] ?? '', '');

        if (! $this->shouldUpdate($metaType, $objectIds)) {
            return $cache;
        }

        try {
            $listProducts = $this->productService->listProducts(ListProductsOperation::seed(['localIds' => $objectIds]));
        } catch (MissingRemoteIdsAfterLocalIdConversionException $exception) {
            // we don't need to report this exception to Sentry
            return $cache;
        } catch (Exception|CommerceExceptionContract $exception) {
            SentryException::getNewInstance($exception->getMessage(), $exception);

            return $cache;
        }

        return $this->update($cache, $listProducts->getProducts());
    }

    /**
     * Determines whether the cache should be updated for a given set.
     *
     * @param string $metaType
     * @param int[] $objectIds local product IDs
     * @return bool
     */
    protected function shouldUpdate(string $metaType, array $objectIds) : bool
    {
        return 'post' === $metaType && ! empty($objectIds);
    }

    /**
     * Updates the cache metadata related to products.
     *
     * @param array<int, array<string, array<mixed>>> $cache
     * @param ProductAssociation[] $productAssociations
     * @return array<int, array<string, array<mixed>>> the updated cache
     */
    protected function update(array $cache, array $productAssociations) : array
    {
        foreach ($productAssociations as $productAssociation) {
            // merges the local product cached metadata with remote metadata from catalog
            $localMeta = $cache[$productAssociation->localId] ?? [];
            $inventorySummary = $this->getInventorySummaryForProduct($productAssociation->remoteResource);

            $cache[$productAssociation->localId] = array_merge(
                $localMeta,
                ProductPostMetaAdapter::getNewInstance($productAssociation->remoteResource)
                    ->setLocalMeta($localMeta)
                    ->setInventorySummary($inventorySummary)
                    ->convertFromSourceToFormattedArray()
            );
        }

        return $cache;
    }

    /**
     * Gets the inventory summary (if available) that corresponds to the supplied product.
     *
     * @param ProductBase $remoteProduct
     *
     * @return Summary|null
     */
    protected function getInventorySummaryForProduct(ProductBase $remoteProduct) : ?Summary
    {
        // bail if not tracking inventory
        if (! isset($remoteProduct->inventory) || ! $remoteProduct->inventory->tracking || ! $remoteProduct->inventory->externalService) {
            return null;
        }

        // bail if the inventory feature reads are disabled
        if (! InventoryIntegration::shouldLoad() || ! InventoryIntegration::hasCommerceCapability(Commerce::CAPABILITY_READ)) {
            return null;
        }

        try {
            // the actual summary will very likely have been cached at this point, and that cache will be returned instead of calling the API
            $summaries = $this->summariesService->list(ListSummariesOperation::seed([
                'productIds' => [$remoteProduct->productId],
            ]))->getSummaries();

            $productSummary = current($summaries);

            return $productSummary instanceof Summary ? $productSummary : null;
        } catch (Exception $exception) {
            SentryException::getNewInstance("Could not read inventory summary for product {$remoteProduct->productId}", $exception);

            return null;
        }
    }
}
