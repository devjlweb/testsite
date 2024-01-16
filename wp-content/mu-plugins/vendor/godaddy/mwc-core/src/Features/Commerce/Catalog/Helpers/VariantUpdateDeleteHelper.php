<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Helpers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\Contracts\ListProductsOperationContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Operations\ListProductsOperation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Providers\DataObjects\ProductAssociation;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsMappingServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Contracts\ProductsServiceContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Catalog\Services\Responses\Contracts\ListProductsResponseContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\Contracts\CommerceExceptionContract;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Exceptions\MissingRemoteIdsAfterLocalIdConversionException;
use GoDaddy\WordPress\MWC\Core\Features\Commerce\Services\Exceptions\CachingStrategyException;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Models\Products\Product;

/**
 * Helper for ensuring that all the local variants match up with what's listed in the platform, and handling any necessary deletions.
 * For example: if a product has "Variant A" and "Variant B", but "B" gets deleted remotely, then we need to ensure that we also delete that "B" variant in the local database.
 */
class VariantUpdateDeleteHelper
{
    /** @var ProductsMappingServiceContract */
    protected ProductsMappingServiceContract $productsMappingService;

    /** @var ProductsServiceContract */
    protected ProductsServiceContract $productsService;

    protected RemoteProductNotFoundHelper $remoteProductNotFoundHelper;

    /**
     * Constructor.
     *
     * @param ProductsMappingServiceContract $productsMappingService
     * @param ProductsServiceContract $productsService
     * @param RemoteProductNotFoundHelper $remoteProductNotFoundHelper
     */
    public function __construct(
        ProductsMappingServiceContract $productsMappingService,
        ProductsServiceContract $productsService,
        RemoteProductNotFoundHelper $remoteProductNotFoundHelper
    ) {
        $this->productsMappingService = $productsMappingService;
        $this->productsService = $productsService;
        $this->remoteProductNotFoundHelper = $remoteProductNotFoundHelper;
    }

    /**
     * Updates and/or deletes local variations of a given product by its post ID.
     *
     * @param int $localId
     * @return void
     * @throws Exception|CommerceExceptionContract
     */
    public function reconcileVariantsForProductByPostId(int $localId) : void
    {
        if ($remoteId = $this->getRemoteIdForLocalId($localId)) {
            $variations = $this->getAndUpdateVariantsForRemoteProduct($remoteId)->getProducts();
            $this->deleteRemotelyDeletedLocalVariations($variations);
        }
    }

    /**
     * Deletes local variations where the corresponding remote resource has been deleted.
     *
     * @param ProductAssociation[] $variations
     * @return void
     */
    protected function deleteRemotelyDeletedLocalVariations(array $variations) : void
    {
        foreach ($variations as $variation) {
            if ($variation->remoteResource->deletedAt !== null) {
                $this->remoteProductNotFoundHelper->handle($variation->localId);
            }
        }
    }

    /**
     * Gets (and also locally updates) local variations of a parent product (by remote IDs).
     *
     * This will insert missing variations {@see AbstractResourceAssociationBuilder::getRemoteResourceLocalId()}
     * which gets called by {@see AbstractListRemoteResourcesService::list()}
     * when calling {@see ProductsService::listProducts()} below.
     *
     * @param string $remoteId
     * @return ListProductsResponseContract
     * @throws CommerceExceptionContract|CachingStrategyException|BaseException|MissingRemoteIdsAfterLocalIdConversionException
     */
    protected function getAndUpdateVariantsForRemoteProduct(string $remoteId) : ListProductsResponseContract
    {
        return $this->productsService->listProducts($this->getVariantsListProductsOperation($remoteId));
    }

    /**
     * Gets the remote commerce ID for a corresponding local product post ID.
     *
     * @param int $localId
     * @return ?string
     */
    protected function getRemoteIdForLocalId(int $localId) : ?string
    {
        return $this->productsMappingService->getRemoteId((new Product())->setId($localId));
    }

    /**
     * Gets the list product operation for variants of a given parent product by ID.
     *
     * @param string $parentId
     * @return ListProductsOperationContract
     */
    protected function getVariantsListProductsOperation(string $parentId) : ListProductsOperationContract
    {
        return ListProductsOperation::getNewInstance()
            ->setParentId($parentId)
            ->setIncludeDeleted(true)
            ->setPageSize(100);
    }
}
